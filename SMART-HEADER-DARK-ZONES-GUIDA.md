# SmartHeader - Sistema Cambio Colore Automatico su Sfondi Scuri

## 📋 Panoramica

Il sistema di **Dark Zone Detection** permette allo SmartHeader di cambiare automaticamente colore del testo, del logo e dei widget quando passa sopra sezioni con sfondo scuro.

**Caratteristiche:**
- ✅ Rilevamento automatico tramite IntersectionObserver
- ✅ Transizioni fluide senza flickering
- ✅ Performance ottimizzate (60fps)
- ✅ Supporto completo per SVG (logo)
- ✅ Compatibile con Elementor Navigation Menu
- ✅ Compatibile con widget BW NavShop
- ✅ Responsive (mobile, tablet, desktop)
- ✅ Accessibilità (prefers-reduced-motion)

---

## 🚀 Come Usare il Sistema

### Passo 1: Marcare le Sezioni Scure

Aggiungi la classe **`.smart-header-dark-zone`** a tutte le sezioni/container con sfondo scuro.

**In Elementor:**
1. Seleziona la sezione con sfondo scuro
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-dark-zone`

```
┌─────────────────────────────────────┐
│ Section Settings                     │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-dark-zone│
└─────────────────────────────────────┘
```

**Esempio HTML:**
```html
<!-- Sezione con sfondo chiaro (normale) -->
<section class="my-hero-section">
    <h1>Benvenuto</h1>
</section>

<!-- Sezione con sfondo scuro (marcata) -->
<section class="my-about-section smart-header-dark-zone" style="background: #000;">
    <h2>Chi Siamo</h2>
</section>

<!-- Sezione con sfondo chiaro (normale) -->
<section class="my-services-section">
    <h3>Servizi</h3>
</section>
```

---

### Passo 2: Marcare gli Elementi Reattivi

Aggiungi la classe **`.smart-header-reactive-text`** agli elementi dello SmartHeader che devono cambiare colore.

#### 2.1 Menu Navigation

**In Elementor:**
1. Seleziona il widget **Nav Menu** dentro lo SmartHeader
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-reactive-text`

```
┌─────────────────────────────────────┐
│ Nav Menu Settings                    │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

#### 2.2 Logo SVG

**In Elementor:**
1. Seleziona il widget **Image** o **Logo** con il tuo logo SVG
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-reactive-text`

**IMPORTANTE:** Il logo deve essere in formato **SVG** perché il sistema cambia il colore tramite `fill: currentColor`. Se usi PNG/JPG, considera di usare un filtro CSS.

```
┌─────────────────────────────────────┐
│ Image/Logo Settings                  │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

#### 2.3 Widget BW NavShop

**In Elementor:**
1. Seleziona il widget **BW NavShop** (Cart/Account)
2. Vai in **Advanced** → **CSS Classes**
3. Aggiungi: `smart-header-reactive-text`

```
┌─────────────────────────────────────┐
│ BW NavShop Settings                  │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

#### 2.4 Testo Custom

Puoi applicare la classe a **qualsiasi** widget di testo, heading, o contenitore:

```
┌─────────────────────────────────────┐
│ Heading/Text Settings                │
├─────────────────────────────────────┤
│ Advanced                             │
│   CSS Classes: smart-header-reactive-text
└─────────────────────────────────────┘
```

---

## 🎨 Comportamento del Sistema

### Stati del Colore

| Scenario | Stato SmartHeader | Colore Testo |
|----------|-------------------|--------------|
| SmartHeader su sfondo chiaro | Normale | Scuro (default/inherit) |
| SmartHeader entra in dark zone | `.smart-header--on-dark` | **Bianco (#ffffff)** |
| SmartHeader esce da dark zone | Normale | Torna a scuro |

### Transizioni

Tutte le transizioni sono **fluide** con durata:
- **Desktop:** 0.3s ease
- **Mobile:** 0.25s ease (più veloce)
- **Accessibilità:** Disabilitate con `prefers-reduced-motion`

### Soglia di Attivazione

Il sistema attiva il cambio colore quando **almeno il 30%** dello SmartHeader è sopra una dark zone. Questo previene flickering durante lo scroll veloce.

---

## 🔧 Personalizzazione Avanzata

### Colori Custom

Se vuoi colori diversi dal bianco, modifica il CSS:

```css
/* Esempio: testo giallo su dark zones */
.smart-header--on-dark .smart-header-reactive-text {
    color: #FFD700 !important; /* Giallo oro */
}
```

### Elementi Secondari con Opacità

Per elementi che devono essere meno visibili:

```html
<!-- In Elementor, aggiungi entrambe le classi -->
CSS Classes: smart-header-reactive-text smart-header-reactive-text--secondary
```

```css
/* Opacità 80% invece di 100% */
.smart-header--on-dark .smart-header-reactive-text.smart-header-reactive-text--secondary {
    color: rgba(255, 255, 255, 0.8) !important;
}
```

### Logo PNG/JPG (Non SVG)

Se il tuo logo è PNG/JPG, usa un filtro CSS:

```css
/* Inverte i colori del logo su dark zone */
.smart-header--on-dark .smart-header-reactive-text img {
    filter: brightness(0) invert(1);
    transition: filter 0.3s ease;
}
```

---

## 🧪 Testing e Debug

### Console API

Il sistema espone un'API pubblica per debugging:

```javascript
// Stato corrente
console.log(window.bwSmartHeader.getState());

// Output esempio:
{
    scrollTop: 450,
    direction: "down",
    isVisible: true,
    isOnDarkZone: true,    // ← SmartHeader su dark zone
    darkZonesCount: 3       // ← Numero dark zones trovate
}

// Forza controllo dark zones
window.bwSmartHeader.recheckDarkZones();

// Vedi tutte le dark zones
console.log(window.bwSmartHeader.getDarkZones());
```

### Attivare Debug Mode

Per vedere i log in console:

```javascript
// Nel file bw-main-elementor-widgets.php, modifica la configurazione:
wp_localize_script('bw-smart-header-script', 'bwSmartHeaderConfig', array(
    'debug' => true, // ← Attiva debug
    'scrollDownThreshold' => 100,
    'blurThreshold' => 50,
    // ...
));
```

Vedrai messaggi tipo:
```
[Smart Header] Dark zones trovate { count: 3 }
[Smart Header] SmartHeader entrato in dark zone { overlapPercentage: "78.50%" }
[Smart Header] SmartHeader uscito da dark zone
```

---

## ✅ Checklist Implementazione

### Setup Iniziale
- [ ] Hai applicato `.smart-header` al container principale dell'header
- [ ] Il plugin BW è attivo e gli assets caricati

### Sezioni Scure
- [ ] Hai identificato tutte le sezioni con sfondo scuro
- [ ] Hai aggiunto `.smart-header-dark-zone` a ogni sezione scura
- [ ] Le sezioni hanno `position: relative` (automatico con la classe)

### Elementi Reattivi
- [ ] Hai aggiunto `.smart-header-reactive-text` al menu navigation
- [ ] Hai aggiunto `.smart-header-reactive-text` al logo (SVG)
- [ ] Hai aggiunto `.smart-header-reactive-text` al widget BW NavShop
- [ ] Hai aggiunto `.smart-header-reactive-text` a eventuali altri testi

### Test
- [ ] Scrolla la pagina e verifica che il colore cambi sulle dark zones
- [ ] Testa su mobile, tablet e desktop
- [ ] Verifica che non ci sia flickering durante lo scroll veloce
- [ ] Controlla la console per errori JavaScript

---

## 🐛 Troubleshooting

### Il colore non cambia

**Possibili cause:**
1. Classe `.smart-header-dark-zone` non applicata correttamente
2. Classe `.smart-header-reactive-text` mancante sugli elementi
3. JavaScript non caricato (controlla console)
4. Cache browser/plugin - fai hard refresh (Ctrl+Shift+R)

**Soluzione:**
```javascript
// Controlla in console
console.log(window.bwSmartHeader.getState());
// Verifica che darkZonesCount > 0
```

### Il cambio colore ha flickering

**Causa:** Soglia overlap troppo bassa

**Soluzione:** Modifica la soglia in `bw-smart-header.js`:
```javascript
// Riga ~344
const overlapThreshold = 30; // Aumenta a 40-50 per ridurre flickering
```

### Il logo SVG non cambia colore

**Causa:** Il SVG ha colori hard-coded invece di `currentColor`

**Soluzione:** Modifica il SVG per usare `currentColor`:
```svg
<!-- Prima (hard-coded) -->
<svg><path fill="#000000" /></svg>

<!-- Dopo (reattivo) -->
<svg><path fill="currentColor" /></svg>
```

### Il colore cambia troppo tardi/presto

**Causa:** L'altezza della dark zone è troppo piccola o la soglia overlap troppo alta

**Soluzione:** Verifica che la dark zone abbia altezza sufficiente (min 200-300px) o abbassa la soglia overlap.

---

## 📱 Responsive Behavior

### Mobile (< 768px)
- Transizioni più veloci (0.25s invece di 0.3s)
- Stessi colori e comportamento
- Performance ottimizzate

### Tablet (768px - 1024px)
- Comportamento identico a desktop

### Desktop (> 1024px)
- Comportamento standard

---

## 🎯 Best Practices

### 1. Contrasto WCAG

Assicurati che il contrasto sia sufficiente:
- Testo bianco su sfondo scuro: ✅ Contrast ratio 21:1
- Testo scuro su sfondo chiaro: ✅ Contrast ratio 21:1

### 2. Performance

- Usa **SVG** per i loghi invece di PNG/JPG
- Limita il numero di dark zones a quelle effettivamente necessarie
- Non applicare `.smart-header-reactive-text` a elementi che non servono

### 3. Coerenza Visiva

- Mantieni uno stile coerente per tutte le dark zones
- Usa lo stesso colore di sfondo scuro (#000, #1a1a1a, etc.)
- Assicurati che tutti gli elementi dello SmartHeader reagiscano allo stesso modo

---

## 📦 File Modificati

Questa implementazione ha modificato i seguenti file:

| File | Modifiche |
|------|-----------|
| `/assets/js/bw-smart-header.js` | Aggiunto sistema IntersectionObserver per dark zone detection |
| `/assets/css/bw-smart-header.css` | Aggiunte classi `.smart-header-dark-zone`, `.smart-header--on-dark`, `.smart-header-reactive-text` |

**Nessuna modifica PHP richiesta** - Il sistema funziona solo con JS e CSS.

---

## 🆘 Supporto

In caso di problemi:

1. **Attiva debug mode** e controlla i log
2. **Usa console API** per verificare lo stato
3. **Ispeziona elementi** per vedere se le classi sono applicate correttamente
4. **Testa con dark zone singola** per isolare il problema
5. **Verifica la versione** del plugin: `window.bwSmartHeader.version` (deve essere >= 2.3.0)

---

## 📄 Esempio Completo

```html
<!-- STRUTTURA PAGINA -->

<!-- SmartHeader (fixed top) -->
<div class="smart-header">
    <!-- Logo SVG -->
    <div class="logo-container smart-header-reactive-text">
        <svg>...</svg>
    </div>

    <!-- Menu Navigation -->
    <nav class="elementor-nav-menu smart-header-reactive-text">
        <a href="#home">Home</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
    </nav>

    <!-- BW NavShop -->
    <div class="bw-navshop smart-header-reactive-text">
        <a href="/cart">Cart</a>
        <a href="/account">Account</a>
    </div>
</div>

<!-- Contenuto Pagina -->

<!-- Sezione chiara (header normale) -->
<section class="hero" style="background: #fff;">
    <h1>Welcome</h1>
</section>

<!-- Sezione scura (header diventa chiaro) -->
<section class="about smart-header-dark-zone" style="background: #000;">
    <h2>About Us</h2>
</section>

<!-- Sezione chiara (header torna normale) -->
<section class="services" style="background: #f5f5f5;">
    <h3>Services</h3>
</section>

<!-- Altra sezione scura (header diventa chiaro) -->
<section class="cta smart-header-dark-zone" style="background: #1a1a1a;">
    <h2>Get Started</h2>
</section>
```

---

## 🎉 Risultato Finale

✅ SmartHeader che cambia automaticamente colore quando passa sopra sezioni scure
✅ Logo, menu e widget reattivi
✅ Sistema modulare e riutilizzabile
✅ Performance ottimizzate
✅ Zero configurazione manuale per pagina

**Basta aggiungere la classe `.smart-header-dark-zone` alle sezioni scure e il sistema fa il resto!**
