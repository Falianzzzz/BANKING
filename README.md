## Avvio

Serve solo Docker Desktop oppure Docker Engine con il plugin Compose.

Il progetto funziona anche senza file `.env`, usando i valori predefiniti del `docker-compose.yaml`.
Se vuoi personalizzare porte o credenziali del database, parti dall'esempio:

```bash
cp .env.example .env
```

Poi modifica `.env` e avvia i container:

```bash
docker compose up --build
```

Poi apri:

- Frontend React: http://localhost:5173
- API PHP/Slim: http://localhost:8080/alunni

## phpMyAdmin

phpMyAdmin e' opzionale. Avvialo solo quando serve:

```bash
docker compose --profile tools up --build
```

Poi apri http://localhost:8081

Credenziali database didattiche:

- Server: `db`
- Utente: valore di `DB_USERNAME`
- Password: valore di `DB_PASSWORD`
- Database: valore di `DB_DATABASE`

## Reset completo

Per cancellare database e dipendenze installate nei volumi Docker:

```bash
docker compose down -v
```

## Deploy con Kamal

Il deploy usa Kamal 2 solo per il backend Slim. MariaDB gira come accessory persistente.

Il frontend React resta fuori dal deploy Kamal: puoi pubblicarlo separatamente come sito statico, oppure tenerlo solo per lo sviluppo in laboratorio.

Prerequisiti:

- Un server Linux raggiungibile via SSH
- Docker installabile o gia' installato sul server
- Un account su un registry Docker, per esempio Docker Hub
- Kamal installato in locale: `gem install kamal`

Prepara le variabili:

```bash
cp .env.example .env
```

Poi modifica `.env` impostando almeno:

- `KAMAL_HOST`: IP o hostname del server
- `KAMAL_IMAGE`: immagine Docker da pubblicare, per esempio `tuo-utente/lamp-slim-react`
- `KAMAL_REGISTRY_USERNAME`: utente del registry Docker
- `KAMAL_REGISTRY_PASSWORD`: password o token del registry Docker
- `DB_PASSWORD` e `DB_ROOT_PASSWORD`: password del database in produzione

Se hai un dominio che punta al server, imposta anche `KAMAL_DOMAIN`. In quel caso Kamal abilita HTTPS automatico con Let's Encrypt.

Primo deploy:

```bash
kamal setup
```

Deploy successivi:

```bash
kamal deploy
```

Comandi utili:

```bash
kamal app logs
kamal accessory logs db
kamal accessory exec db "mariadb -uscuola -p"
```

La configurazione Kamal e' in `config/deploy.yml`. I segreti vengono letti da `.kamal/secrets`, che punta alle variabili definite nell'ambiente o nel file `.env`.

Dopo il deploy, l'API sara' disponibile su:

- `https://KAMAL_DOMAIN/api/alunni`, se hai impostato `KAMAL_DOMAIN`
- `http://KAMAL_HOST/api/alunni`, se usi solo l'IP del server

## Struttura

- `app`: frontend React con Vite
- `php`: API PHP con Slim
- `build/init.sql`: schema e dati iniziali del database
- `config/deploy.yml`: configurazione Kamal per il deploy

Database, `node_modules` e `vendor` vivono in volumi Docker, quindi non sporcano il repository.
