# 🎯 Smart Header System - Guida all'uso

## ✅ Sistema Integrato e Funzionante

Il sistema Smart Header è ora **completamente integrato** nel plugin BW Elementor Widgets e carica automaticamente tutti i file necessari.

---

## 📋 Come Usare il Smart Header

### 1. Aggiungi la classe CSS in Elementor

Per attivare il sistema Smart Header sul tuo header, devi aggiungere la classe CSS `smart-header` al container principale dell'header:

**PASSAGGI IN ELEMENTOR:**

1. Apri il tuo **Header** in Elementor (Template → Theme Builder → Header)
2. Clicca sul **CONTAINER/SECTION principale** dell'header (quello più esterno che contiene tutto)
3. Nel pannello di sinistra, vai alla tab **"AVANZATE"** (icona ingranaggio ⚙️)
4. Trova la sezione **"CSS ID & Classi"**
5. Nel campo **"Classi CSS"** scrivi esattamente: `smart-header`
6. Clicca su **"Aggiorna"** per salvare

---

## 🎬 Comportamento del Smart Header

Una volta attivato, il sistema funziona automaticamente:

### ✅ Scroll DOWN (verso il basso)
- Quando scorri **giù oltre 100px**, l'header si **nasconde** scivolando verso l'alto
- Transizione smooth e fluida

### ✅ Scroll UP (verso l'alto)
- Appena scorri **su** (anche di poco), l'header **riappare** immediatamente
- Sempre visibile quando scorri verso l'alto

### ✅ Effetto Blur
- Dopo **50px di scroll**, l'header diventa **semi-trasparente** con effetto blur
- Background con backdrop-filter per un effetto moderno
- Box shadow leggera per dare profondità

### ✅ Posizione Fissa
- L'header rimane sempre **fisso in cima** alla pagina
- Il sistema calcola automaticamente l'altezza e aggiunge il padding necessario al body

---

## 🔧 File Integrati

Il sistema è composto da questi file:

```
/assets/css/bw-smart-header.css     ← Stili CSS
/assets/js/bw-smart-header.js       ← JavaScript per la logica
```

### Caricamento Automatico

I file vengono caricati automaticamente dal plugin **bw-main-elementor-widgets.php** tramite:
- `bw_enqueue_smart_header_assets()` - Funzione che registra e carica CSS e JS
- Hook `wp_enqueue_scripts` - Carica i file solo sul frontend (NON nell'editor Elementor)

---

## 🧪 Come Testare se Funziona

### Test 1: Verifica classe CSS
1. Apri il tuo sito WordPress (frontend, non editor)
2. Premi **F12** per aprire Developer Tools
3. Ispeziona l'header con il selettore elemento
4. Verifica che il container abbia la classe `smart-header`

### Test 2: Verifica caricamento file
1. Apri Developer Tools (F12)
2. Vai alla tab **"Network"**
3. Ricarica la pagina (Ctrl+R)
4. Cerca i file:
   - `bw-smart-header.css`
   - `bw-smart-header.js`
5. Devono essere entrambi caricati con status 200

### Test 3: Verifica funzionamento
1. Apri la **Console** in Developer Tools (F12 → Console)
2. Cerca il messaggio: `[Smart Header] ✅ Smart Header System inizializzato con successo`
3. Se vedi un warning `⚠️ Elemento non trovato`, verifica di aver aggiunto la classe

### Test 4: Test scroll
1. Scrolla la pagina **verso il basso** per almeno 100px
   - ✅ L'header deve scomparire
2. Scrolla **verso l'alto**
   - ✅ L'header deve riapparire immediatamente
3. Scrolla oltre 50px
   - ✅ Deve apparire l'effetto blur e la box shadow

---

## ⚙️ Personalizzazioni

### Modificare i parametri di scroll

Apri il file `/assets/js/bw-smart-header.js` e modifica la sezione `CONFIG`:

```javascript
const CONFIG = {
    scrollThreshold: 100,    // Pixel prima di nascondere l'header (aumenta per nascondere più tardi)
    scrollDelta: 5,          // Sensibilità scroll (diminuisci per reagire a scroll più piccoli)
    blurThreshold: 50,       // Quando attivare blur (diminuisci per blur immediato)
    hideDelay: 0,            // Delay prima di nascondere (in millisecondi)
    showDelay: 0,            // Delay prima di mostrare (in millisecondi)
    throttleDelay: 100,      // Performance throttling (aumenta se hai lag)
    debug: false             // Cambia a true per vedere log dettagliati in console
};
```

### Modificare colori e trasparenza

Apri il file `/assets/css/bw-smart-header.css` e modifica:

```css
/* Background normale */
.smart-header {
    background-color: rgba(255, 255, 255, 0.95) !important;
}

/* Background con scroll */
.smart-header.scrolled {
    background-color: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(12px);  /* Intensità blur */
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08) !important;
}
```

### Tema Scuro

Per usare un tema scuro, aggiungi **due classi** in Elementor:
- `smart-header` (obbligatoria)
- `dark-theme` (opzionale)

Il CSS include già gli stili per il tema scuro!

---

## 🔧 Risoluzione Problemi

### ❌ L'header non si nasconde/mostra

**SOLUZIONI:**
1. Verifica che la classe `smart-header` sia applicata correttamente
2. Apri la Console (F12) e cerca errori JavaScript
3. Verifica che i file CSS e JS siano caricati (tab Network)
4. Svuota la cache di WordPress
5. Controlla che non ci siano conflitti con altri plugin di sticky header

### ❌ Il blur non funziona

**SOLUZIONI:**
1. Il blur potrebbe non essere supportato dal browser (verifica con Chrome o Firefox aggiornati)
2. Il fallback automatico mostra comunque un background opaco
3. Alcuni browser vecchi non supportano `backdrop-filter`

### ❌ L'header copre il contenuto

**SOLUZIONI:**
1. Il padding viene calcolato automaticamente tramite CSS variable `--smart-header-height`
2. Se non funziona, puoi impostare manualmente nel CSS:
   ```css
   body:not(.elementor-editor-active) {
       padding-top: 120px; /* Sostituisci con l'altezza del tuo header */
   }
   ```

### ❌ Nell'editor Elementor l'header è fisso

**SOLUZIONI:**
1. Questo NON dovrebbe accadere, il JavaScript disabilita il sistema nell'editor
2. Verifica che il file JS sia caricato correttamente
3. Prova a ricaricare l'editor (svuota cache browser con Ctrl+Shift+R)

### ❌ Voglio vedere i log di debug

**SOLUZIONI:**
1. Apri `/assets/js/bw-smart-header.js`
2. Cambia `debug: false` in `debug: true`
3. Apri la Console (F12 → Console)
4. Ricarica la pagina e scorri
5. Vedrai log dettagliati di ogni azione

---

## 📱 Mobile e Responsive

Il sistema è completamente responsive:
- Transizioni leggermente più veloci su mobile
- Blur meno intenso su dispositivi mobile per migliori performance
- Compatibile con bounce scroll iOS

---

## ♿ Accessibilità

Il sistema rispetta le preferenze utente:
- `prefers-reduced-motion: reduce` → Disabilita le animazioni per utenti con disturbi vestibolari
- Cross-browser compatibility con fallback automatici

---

## 📊 Performance

Ottimizzazioni implementate:
- ✅ **requestAnimationFrame** per animazioni smooth sincronizzate
- ✅ **Throttling** degli eventi scroll per ridurre il carico
- ✅ **GPU acceleration** con transform e will-change
- ✅ **Passive event listeners** per migliori performance scroll
- ✅ **Calcolo dinamico** dell'altezza header con CSS variables

---

## 🚀 Checklist Finale

Prima di considerare il lavoro completato, verifica:

- [ ] Ho aggiunto la classe `smart-header` al container header in Elementor
- [ ] Ho salvato e pubblicato le modifiche in Elementor
- [ ] I file CSS e JS vengono caricati (verificato in Network tab)
- [ ] La Console mostra il messaggio di inizializzazione
- [ ] L'header si nasconde quando scrolo giù
- [ ] L'header riappare quando scrolo su
- [ ] L'effetto blur funziona dopo 50px di scroll
- [ ] Su mobile funziona correttamente
- [ ] Nell'editor Elementor l'header NON è fisso

---

## 📞 Supporto

Se hai ancora problemi:

1. Attiva `debug: true` nel JavaScript
2. Apri Console DevTools e copia tutti i messaggi
3. Verifica la tab Network per vedere se i file vengono caricati
4. Ispeziona l'elemento header e verifica quali classi CSS sono applicate

---

## 🎉 Fine!

Il tuo sistema Smart Header è ora completamente funzionante e integrato nel plugin BW Elementor Widgets!

**Versione:** 1.0.0
**Ultimo aggiornamento:** 2025
