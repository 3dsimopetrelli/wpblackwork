# BW Elementor Widgets

Collezione di **widget personalizzati per Elementor** sviluppati per BW.  
Questo plugin raccoglie più widget, ognuno organizzato in file separati per garantire modularità e scalabilità.

---

## 📂 Struttura cartelle

```
bw-elementor-widgets/
│── bw-main-elementor-widgets.php        // file principale del plugin
│── includes/
│    │── class-bw-widget-loader.php      // loader automatico dei widget
│    │── widgets/
│    │    └── class-bw-slick-slider-widget.php
│── assets/
│    ├── css/
│    │    └── bw-slick-slider.css
│    └── js/
│         ├── bw-slick-slider.js
│         └── bw-slick-slider-admin.js
```

---

## ⚙️ Installazione

1. Clona o scarica la cartella `bw-elementor-widgets` in `wp-content/plugins/`.
2. Verifica che Elementor sia installato e attivo.
3. Attiva il plugin **BW Elementor Widgets** da **Plugin > Aggiungi nuovo** su WordPress.
4. Troverai i nuovi widget nella dashboard Elementor, sotto la categoria **General** (o personalizzata).

---

## 🚀 Widget attuali

### BW Slick Slider
Uno slider basato su **Slick Carousel** che mostra post o prodotti WooCommerce, con configurazioni per query, layout e gestione delle categorie.

---

## 🛠 Aggiungere un nuovo widget

1. Crea un nuovo file in `includes/widgets/` con il nome:  
   ```
   class-bw-nome-widget.php
   ```
   La classe deve chiamarsi:
   ```
   Widget_Bw_Nome_Widget
   ```

2. Aggiungi eventuali CSS in `assets/css/bw-nome-widget.css`.  
3. Aggiungi eventuali JS in `assets/js/bw-nome-widget.js`.  
4. Il **loader** (`class-bw-widget-loader.php`) registrerà automaticamente il nuovo widget, non serve modificare altro.  

---

## 📦 Dipendenze

- [Elementor](https://elementor.com/)  
- [Slick Carousel](https://kenwheeler.github.io/slick/)

---

## 👨‍💻 Autore
Simone
