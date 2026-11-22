# Specifiche

## Register
troverai una rotta `POST /api/user/register` che accetta come body
```
    {"username":"string", "password":"string", "name":"string", "lastname":"string"}
```
gli "string" nel json sono dei placeholder
React deve controllare che lato interfaccia i campi siano compilati tutti usando la libreria zod per la validazione

Se tutto ok, il payload viene poi inviato al backend, se non si verificano errori il backend 
risponde con il messaggio 

```
 {'ok': 'Utente registrato correttamente'}
```

## Login
deve sfruttare le api esposte dal backend
troverai una rotta `POST /api/user/login` che accetta come body 
```
    {"username":"string", "password":"string"}
```
i due "string" nel json di esempio sono dei placeholder

l'api di login, se avvenuta con successo, restituisce un json di questo tipo

``` 
{"token":"5|jkPFc1Lc7GM6SAd3CfWvS1V04GSqhRV0GAoXkvQV159d2bfd"}
``` 
il contenuto della chiave `token` viene salvato nel sessionStorage per essere usato nelle chiamate verso le api del backend

in caso di login fallito, l'api risponderà con un 401 (o comunque un http code diverso da 200), compare un alert contenente un errore

## Lista Ristoranti
nel backend c'è la rotta `GET /api/restaurants/list`, la quale restituisce la lista 
dei ristoranti presenti nel sistema. Questa api è protetta da token, quindi se non si spedisce nell'header `Authorization: Bearer <token>` il token generato prima, l'api risponderà sempre 401.
- una volta effettuata la chiamata, se l'api restituisce una lista vuota, il frontend React adegua la pagina con il messaggio "Attenzione: nessun ristorante disponibile al momento"
- se l'api restituisce 401 (o comunque un http code diverso da 200), compare un alert con la scritta "Utenza non valida"
- se invece l'api restituisce una lista, per ognuno di esso viene mostrato:
    - un'immagine, per adesso un file fisso, in seguito da sostituire nella pagina CRUD di Filament (per info, url del file https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=500&auto=format&fit=crop&q=60)
    - il nome del ristorante
    - l'indirizzo
    - un pulsante che punta alla pagina di dettaglio /restaurants/{id}

## Dettaglio Ristoranti
nel backend c'è la rotta `GET /api/restaurants/{id}/menu`, la quale recupera il menu dei
piatti offerto da quel ristorante e le info dell'anagrafica del ristorante, con una risposta di questo tipo:
```
{"restaurant": {"name", "type", "address", "city", "CAP"}, "menu": [{"id", "category", "dishes": [{"id", "name", "price", "price_id"}]}]}
```
la pagina salva `restaurant` e `menu` in due stati React (non arrivano come props, nessuno le passa al componente).
Questa api è protetta da token, quindi se non si spedisce nell'header `Authorization: Bearer <token>` 
il token generato prima, l'api risponderà sempre 401.
- La pagina prima mostra le info generiche del ristorante:
    - nome
    - tipo del ristorante
    - indirizzo, città, CAP
- Sotto il blocco delle info c'è un blocco con il menu dei piatti
- una volta fatta la chiamata, per ogni categoria di piatti viene creata una tab dedicata
- dentro ogni tab, per ogni piatto presente in una categoria, vengono mostrate le seguenti info:
    - un'immagine del piatto, per ora fissa `/images/img-restaurant.jpeg`
    - il nome del piatto
    - il prezzo del piatto
    - 2 pulsanti, uno per aggiungere e uno per togliere dal carrello
- il carrello è mostrato nella parte destra della pagina, i dati vengono gestiti
tramite i context di React e salvati nella chiave `cart` in sessionStorage, per
condividere il contenuto del carrello tra i vari elementi della pagina, sessionStorage
per non perderlo al refresh
    - il carrello mostra l'elenco dei piatti scelti, la qta, il prezzo e i pulsanti per aggiungere o togliere i piatti dal carrello
    - il carrello all'inizio parte vuoto con il messaggio "Non hai selezionato alcun piatto."
    - man mano che si aggiungono piatti, viene anche aggiornato il totale di quanto si va a spendere
    - c'è infine il pulsante "Ordina", il quale recupera i dati del carrello, contatta l'api `POST /api/order/place`, la quale chiama le api di Stripe per procedere poi con il pagamento fuori dal sito
        - il body inviato all'api è il seguente: `{"cart": [{"price_id", "quantity", ...}], "restaurant_id": <id>}`
        - la response è di questo tipo `{"url": "..."}`, il frontend effettua quindi il redirect alla pagina ricevuta dall'api per andare su Stripe e completare il pagamento
    - in seguito al pagamento si viene reindirizzati alla pagina `/payments/ok`

## Pagamento OK
Dopo che è stato completato il pagameno su Stripe, viene fatto un redirect a questa pagina, 
che ha il solo scopo di svuotare il carrello dal sessionStorage()