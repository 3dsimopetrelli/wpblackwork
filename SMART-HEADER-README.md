# 🎯 BW Smart Header System - Guida all'uso

Sistema completo di smart header per WordPress con Elementor integrato nel plugin BW Elementor Widgets.

## 📋 Caratteristiche

✅ **Scroll intelligente**
- Scroll DOWN (>100px) → Header si nasconde verso l'alto
- Scroll UP (anche minimo) → Header riappare immediatamente
- Transizioni smooth e fluide

✅ **Effetto Blur**
- Attivo dopo 50px di scroll
- Background semi-trasparente con backdrop-filter
- Box shadow elegante

✅ **Performance ottimizzate**
- requestAnimationFrame per animazioni fluide
- Throttling degli eventi scroll
- GPU acceleration
- Passive event listeners

✅ **Compatibilità Elementor**
- Funziona nel frontend
- Funziona nell'anteprima di Elementor
- Non interferisce con l'editor

---

## 🚀 Come utilizzare

### Step 1: Apri il tuo header in Elementor

1. Vai in **Elementor → Header** (o Template → Header)
2. Modifica il template del tuo header

### Step 2: Aggiungi la classe "SmartAdder"

1. Clicca sul **container principale** dell'header (quello più esterno)
2. Vai nella tab **Avanzate**
3. Trova la sezione **CSS Classes**
4. Inserisci: `SmartAdder`

![Esempio aggiunta classe](https://i.imgur.com/example.png)

### Step 3: Salva e pubblica

1. Clicca su **Aggiorna** o **Pubblica**
2. Vai sul frontend del sito
3. Scrolla la pagina per vedere l'effetto!

---

## 🎨 Personalizzazioni

### Modificare l'altezza del padding

Apri il file `/assets/css/bw-smart-header.css` e modifica:

```css
body:not(.elementor-editor-active) {
    padding-top: 100px; /* 👈 Modifica questo valore con l'altezza del tuo header */
}
```

**Mobile:**
```css
@media (max-width: 768px) {
    body:not(.elementor-editor-active) {
        padding-top: 80px; /* 👈 Modifica per mobile */
    }
}
```

### Modificare le soglie di scroll

Apri il file `/assets/js/bw-smart-header.js` e modifica l'oggetto `CONFIG`:

```javascript
const CONFIG = {
    scrollThreshold: 100,   // Pixel prima di nascondere header
    scrollDelta: 5,         // Sensibilità movimento scroll
    blurThreshold: 50,      // Quando inizia blur effect
    hideDelay: 0,           // Delay prima di nascondere (ms)
    showDelay: 0,           // Delay prima di mostrare (ms)
    throttleDelay: 100,     // Throttle scroll events
    debug: false            // Attiva log in console
};
```

### Attivare la modalità debug

Cambia `debug: false` in `debug: true` nel file JavaScript per vedere i log in console.

### Variante Dark Theme

Aggiungi entrambe le classi al container: `SmartAdder dark-theme`

---

## 🔧 Configurazioni avanzate

### Modificare la velocità delle transizioni

Nel file CSS, modifica:

```css
.SmartAdder {
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), /* 👈 Cambia 0.4s */
                background-color 0.3s ease,
                backdrop-filter 0.3s ease,
                box-shadow 0.3s ease !important;
}
```

- Valori più bassi (es. `0.2s`) = transizione più veloce
- Valori più alti (es. `0.6s`) = transizione più lenta

### Modificare l'intensità del blur

Nel file CSS:

```css
.SmartAdder.scrolled {
    backdrop-filter: blur(12px); /* 👈 Cambia 12px */
    -webkit-backdrop-filter: blur(12px);
}
```

- Valori più alti (es. `20px`) = blur più intenso
- Valori più bassi (es. `6px`) = blur più leggero

### Modificare la trasparenza del background

Nel file CSS:

```css
.SmartAdder {
    background-color: rgba(255, 255, 255, 0.95) !important; /* 👈 L'ultimo valore è la trasparenza */
}

.SmartAdder.scrolled {
    background-color: rgba(255, 255, 255, 0.85) !important; /* 👈 Più basso = più trasparente */
}
```

Il valore finale (`0.95`, `0.85`) rappresenta l'opacità:
- `1.0` = completamente opaco
- `0.0` = completamente trasparente

---

## ✅ Testing Checklist

Prima di considerare l'installazione completa, verifica:

- [ ] **Scroll Down**: L'header si nasconde dopo 100px di scroll
- [ ] **Scroll Up**: L'header riappare con movimento minimo verso l'alto
- [ ] **Blur Effect**: Dopo 50px appare effetto blur e shadow
- [ ] **Stato Iniziale**: L'header è visibile al caricamento
- [ ] **Editor Elementor**: L'header funziona normalmente nell'editor
- [ ] **Anteprima Elementor**: Funziona correttamente nell'anteprima
- [ ] **Mobile**: Funziona su dispositivi mobile
- [ ] **Performance**: Nessun lag durante lo scroll
- [ ] **Console**: Nessun errore JavaScript

---

## 🐛 Troubleshooting

### L'header non si nasconde/mostra

1. Verifica che la classe `SmartAdder` sia applicata al container corretto
2. Apri la Console del browser (F12) e cerca errori JavaScript
3. Attiva debug mode (`debug: true` nel JS) per vedere i log
4. Svuota la cache del browser (Ctrl+Shift+R)

### L'effetto blur non funziona

1. Il blur potrebbe non essere supportato dal browser
2. Prova su Chrome, Firefox o Edge aggiornati
3. Il fallback automatico mostrerà un background opaco

### C'è un salto durante lo scroll

1. Verifica che non ci siano altri script che interferiscono
2. Riduci `throttleDelay` nel CONFIG a `50` (consuma più risorse)
3. Assicurati che l'header non contenga elementi troppo pesanti

### Il padding del body non è corretto

1. Misura l'altezza reale del tuo header con DevTools
2. Modifica `padding-top` nel CSS (riga ~96)
3. Modifica anche il valore mobile se necessario (riga ~173)

### Problemi nell'anteprima di Elementor

1. Svuota la cache di Elementor
2. Ricarica l'anteprima (F5)
3. Verifica che il JavaScript sia caricato (apri Console e cerca "[BW Smart Header]")

---

## 📁 Struttura file

```
wpblackwork/
├── assets/
│   ├── css/
│   │   └── bw-smart-header.css      # Stili del sistema
│   └── js/
│       └── bw-smart-header.js       # Logica JavaScript
├── bw-main-elementor-widgets.php    # File principale con enqueue
└── SMART-HEADER-README.md           # Questa guida
```

---

## 🔄 Aggiornamenti

Quando modifichi i file CSS o JavaScript:

1. Salva le modifiche
2. Svuota la cache del browser (Ctrl+Shift+R)
3. Svuota la cache di WordPress (se usi plugin di cache)
4. Ricarica la pagina

Il sistema usa `filemtime()` per il versioning automatico, quindi gli aggiornamenti vengono rilevati automaticamente.

---

## ⚙️ Compatibilità

✅ WordPress 5.0+
✅ Elementor 3.0+
✅ PHP 7.4+
✅ Browser moderni (Chrome, Firefox, Safari, Edge)
✅ Mobile iOS e Android

---

## 📝 Note tecniche

- **Selettore CSS**: `.SmartAdder` (case-sensitive!)
- **Dipendenze**: Nessuna (Vanilla JavaScript)
- **Conflitti**: Nessuno noto
- **Performance**: Ottimizzata con requestAnimationFrame e throttling
- **Accessibilità**: Supporto per `prefers-reduced-motion`

---

## 💡 Consigli

1. **Mantieni l'header leggero**: Evita troppe animazioni CSS complesse nell'header
2. **Usa immagini ottimizzate**: WebP o SVG per logo
3. **Testa su dispositivi reali**: Non solo emulatore
4. **Monitora le performance**: Usa Chrome DevTools → Performance

---

## 🆘 Supporto

Per problemi o domande:

1. Attiva `debug: true` nel JavaScript
2. Apri Console del browser (F12)
3. Copia i messaggi di errore/log
4. Condividi screenshot del problema

---

**Versione**: 1.0.0
**Ultimo aggiornamento**: 2025
**Compatibilità**: WordPress + Elementor
