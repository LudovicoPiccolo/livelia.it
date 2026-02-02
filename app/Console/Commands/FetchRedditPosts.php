<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchRedditPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livelia:fetch_reddit';

    protected $description = 'Fetch news from Reddit r/Italia';

    public function handle()
    {
        $topics = \App\Models\RedditTopic::where('is_active', true)->get();

        if ($topics->isEmpty()) {
            $this->warn('No active Reddit topics found.');

            return 0;
        }

        foreach ($topics as $topic) {
            $this->info("Fetching data for topic: r/{$topic->name}");

            $url = "https://www.reddit.com/r/{$topic->name}/new/.json?limit=25";

            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'LiveliaBot/1.0',
                ])->get($url);

                if ($response->failed()) {
                    $this->error("  Failed to fetch data for {$topic->name}: ".$response->body());

                    continue; // Skip to next topic
                }

                $data = $response->json();
                $posts = $data['data']['children'] ?? [];

                $this->info('  Found '.count($posts).' posts.');

                foreach ($posts as $postData) {
                    $post = $postData['data'];

                    // Basic validation
                    if (! isset($post['title']) || ! isset($post['id'])) {
                        continue;
                    }

                    \App\Models\RedditPost::updateOrCreate(
                        ['reddit_id' => $post['name']], // t3_... ID
                        [
                            'title' => $post['title'],
                            'content' => $post['selftext'] ?? '',
                            'url' => $post['url'] ?? "https://www.reddit.com{$post['permalink']}",
                            'author' => $post['author'] ?? 'unknown',
                            'subreddit' => $post['subreddit'] ?? $topic->name,
                            'published_at' => \Carbon\Carbon::createFromTimestamp($post['created_utc']),
                            'raw_data' => $post,
                        ]
                    );
                }

                $this->info("  Updated posts for r/{$topic->name}.");

                // Be nice to Reddit API
                sleep(2);

            } catch (\Exception $e) {
                $this->error("  An error occurred for r/{$topic->name}: ".$e->getMessage());
            }
        }

        return 0;
    }
}
