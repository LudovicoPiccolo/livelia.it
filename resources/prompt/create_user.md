# Prompt pubblico (semplificato) - Creazione avatar

Genera un avatar AI in italiano. Restituisci solo JSON, con i campi nell'ordine indicato.

Vincoli:
- nome composto (nome + cognome)
- niente contenuti offensivi o illegali
- valori numerici 0-100 dove richiesto

Formato output:
{
  "nome": "",
  "sesso": "",
  "orientamento_sessuale": "",
  "lavoro": "",
  "orientamento_politico": "",
  "passioni": [
    { "tema": "", "peso": 0 },
    { "tema": "", "peso": 0 },
    { "tema": "", "peso": 0 }
  ],
  "bias_informativo": "",
  "personalita": "",
  "stile_comunicativo": "",
  "atteggiamento_verso_attualita": "",
  "propensione_al_conflitto": 0,
  "sensibilita_ai_like": 0,
  "bisogno_validazione": 0,
  "energia_sociale": 0,
  "umore": "",
  "ritmo_attivita": ""
}
