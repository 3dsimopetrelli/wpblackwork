# BW Cart Pop-Up Module

Modulo per gestire un pannello cart pop-up laterale con slide-in animato per WooCommerce.

## Descrizione

Il modulo Cart Pop-Up aggiunge al plugin BW Elementor Widgets la possibilità di mostrare il carrello WooCommerce in un pannello laterale slide-in invece di reindirizzare alla pagina del carrello quando si clicca su "Add to Cart".

## Caratteristiche

- **Toggle ON/OFF**: Attiva/disattiva la funzionalità dal pannello admin
- **Pannello Slide-in**: Animazione fluida da destra verso sinistra con overlay scuro
- **Completamente Configurabile**: Tutti i colori, dimensioni e testi personalizzabili da admin
- **Design Responsive**: Ottimizzato per desktop, tablet e mobile
- **Promo Code**: Box per inserire coupon con calcolo real-time degli sconti
- **Mini Cart Completo**: Lista prodotti, quantità modificabile, rimozione prodotti, totali
- **CSS Personalizzabile**: File CSS completamente commentato per modifiche manuali

## Struttura File

```
cart-popup/
├── cart-popup.php                    # File principale del modulo
├── admin/
│   └── settings-page.php            # Pannello admin con impostazioni
├── frontend/
│   └── cart-popup-frontend.php      # Logica frontend e AJAX handlers
├── assets/
│   ├── css/
│   │   └── bw-cart-popup.css       # Stili del cart pop-up (completo e commentato)
│   └── js/
│       └── bw-cart-popup.js        # JavaScript per interazioni
└── README.md                        # Questa documentazione
```

## Installazione

Il modulo è già integrato nel plugin BW Elementor Widgets. Non serve installazione separata.

## Configurazione

1. Vai nel pannello admin di WordPress
2. Clicca su **Cart Pop-Up** nel menu laterale
3. Configura le seguenti opzioni:

### Impostazioni Disponibili

#### Generale
- **Attiva Cart Pop-Up**: Toggle per attivare/disattivare la funzionalità
- **Larghezza Pannello**: Larghezza del pannello laterale in pixel (default: 400px)

#### Overlay
- **Colore Overlay**: Colore della maschera che oscura la pagina
- **Opacità Overlay**: Opacità dell'overlay da 0 a 1 (default: 0.5)

#### Pannello
- **Colore Sfondo Pannello**: Colore di sfondo del pannello slide-in

#### Pulsanti
- **Testo Pulsante Checkout**: Personalizza il testo del pulsante (default: "Proceed to checkout")
- **Colore Pulsante Checkout**: Colore di sfondo del pulsante checkout (default: verde #28a745)
- **Testo Pulsante Continue Shopping**: Personalizza il testo del pulsante (default: "Continue shopping")
- **Colore Pulsante Continue Shopping**: Colore di sfondo del pulsante continue

#### Icone (CSS Custom)
- **CSS Icona Carrello**: CSS personalizzato per sostituire l'icona del carrello
- **CSS Icona Chiusura**: CSS personalizzato per sostituire l'icona X

### Esempio CSS Personalizzato Icone

```css
/* Per sostituire l'icona del carrello */
background-image: url('https://tuosito.com/path/to/cart-icon.svg');
background-size: 24px 24px;

/* Per sostituire l'icona di chiusura */
background-image: url('https://tuosito.com/path/to/close-icon.svg');
background-size: 20px 20px;
```

## Funzionalità

### Comportamento Add to Cart

**Quando OFF (Disattivato)**:
- I pulsanti "Add to Cart" funzionano in modo standard
- L'utente viene reindirizzato alla pagina del carrello

**Quando ON (Attivato)**:
- Cliccando su "Add to Cart" si apre il pannello slide-in
- Overlay scuro copre la pagina
- Il pannello scorre da destra verso sinistra con animazione fluida

### Pannello Cart Pop-Up

Il pannello include:

1. **Header**
   - Icona carrello
   - Titolo "Your Cart"
   - Pulsante chiusura (X)

2. **Lista Prodotti**
   - Immagine prodotto
   - Nome prodotto (cliccabile)
   - Input quantità modificabile
   - Prezzo
   - Pulsante rimozione (X)

3. **Divider**
   - Linea divisoria elegante

4. **Sezione Promo Code**
   - Link "Have a promo code? Click here."
   - Box fade-in con input coupon e pulsante Apply
   - Messaggio di successo/errore
   - Calcolo real-time dello sconto

5. **Totali**
   - Subtotal
   - Discount (se applicato)
   - VAT
   - Total (evidenziato)

6. **Footer**
   - Pulsante "Proceed to checkout" (verde, larghezza 100%)
   - Pulsante "Continue shopping" (grigio)

### Interazioni

- **Chiudi Pannello**: Click sull'overlay, pulsante X, pulsante "Continue shopping", tasto ESC
- **Modifica Quantità**: Cambio valore input aggiorna il carrello in tempo reale
- **Rimuovi Prodotto**: Click sulla X del prodotto
- **Applica Coupon**: Click su "Click here" → inserisci codice → Apply
- **Checkout**: Click su "Proceed to checkout" porta alla pagina di checkout

## AJAX Endpoints

Il modulo espone i seguenti endpoint AJAX:

- `bw_cart_popup_get_contents`: Ottiene il contenuto del carrello
- `bw_cart_popup_apply_coupon`: Applica un codice coupon
- `bw_cart_popup_remove_item`: Rimuove un prodotto dal carrello
- `bw_cart_popup_update_quantity`: Aggiorna la quantità di un prodotto

## Personalizzazione CSS

Il file `assets/css/bw-cart-popup.css` è completamente commentato e diviso in sezioni:

1. **OVERLAY**: Maschera scura di sfondo
2. **PANNELLO PRINCIPALE**: Contenitore slide-in
3. **HEADER DEL PANNELLO**: Intestazione con titolo e pulsanti
4. **ICONE**: Icone carrello e chiusura (personalizzabili)
5. **CONTENUTO CARRELLO**: Area scrollabile
6. **PRODOTTI NEL CARRELLO**: Layout lista prodotti
7. **DIVIDER**: Linea divisoria
8. **SEZIONE PROMO CODE**: Box coupon con animazioni
9. **TOTALI**: Riepilogo prezzi
10. **FOOTER E PULSANTI**: Pulsanti azione
11. **TRANSIZIONI E ANIMAZIONI**: Effetti smooth
12. **UTILITIES**: Classi helper
13. **RESPONSIVE**: Adattamenti mobile e tablet

### Modifiche CSS Comuni

```css
/* Cambia larghezza pannello */
.bw-cart-popup-panel {
    width: 500px; /* Invece di 400px */
}

/* Cambia colore overlay */
.bw-cart-popup-overlay.active {
    background-color: rgba(0, 0, 0, 0.7); /* Invece di 0.5 */
}

/* Cambia colore pulsante checkout */
.bw-cart-popup-checkout {
    background-color: #ff6600 !important; /* Arancione */
}

/* Cambia velocità animazione */
.bw-cart-popup-panel {
    transition: transform 0.6s ease; /* Invece di 0.4s */
}
```

## Compatibilità

- **WordPress**: 5.0+
- **WooCommerce**: 4.0+
- **PHP**: 7.4+
- **Browser**: Chrome, Firefox, Safari, Edge (ultime 2 versioni)

## Note Tecniche

- Il modulo usa `added_to_cart` event di WooCommerce per intercettare l'aggiunta al carrello
- Gli assets vengono caricati solo se la funzionalità è attiva
- Il pannello usa `position: fixed` e `z-index: 9999`
- Lo scroll del body viene bloccato quando il pannello è aperto
- Le transizioni usano `cubic-bezier` per animazioni fluide
- I totali vengono ricalcolati automaticamente via AJAX

## Troubleshooting

### Il pannello non si apre
1. Verifica che la funzionalità sia attiva nel pannello admin
2. Controlla che WooCommerce sia installato e attivo
3. Verifica console JavaScript per errori

### I coupon non funzionano
1. Verifica che i coupon siano configurati correttamente in WooCommerce
2. Controlla le condizioni di applicazione del coupon
3. Verifica che il carrello abbia prodotti validi per il coupon

### Conflitti CSS
1. Aumenta la specificità dei selettori nel CSS custom
2. Usa `!important` se necessario
3. Verifica che non ci siano altri plugin che modificano il carrello

## Supporto

Per problemi o domande, contatta il team di sviluppo del plugin BW Elementor Widgets.

## Changelog

### 1.0.0 (2025-11-17)
- Release iniziale
- Pannello admin con tutte le configurazioni
- Pannello slide-in frontend
- Funzionalità promo code
- CSS completo e commentato
- Responsive design
- AJAX per aggiornamenti real-time

## Crediti

Sviluppato per BW Elementor Widgets
