# Start Chat — GO

Questo file e' il punto di ingresso rapido per una nuova chat GPT/Codex sul repository Blackwork.

## 1) Cosa leggere per partire
1. `Start Chat/doc1.md`
2. `Start Chat/doc2.md`
3. `Start Chat/doc3.md`
4. `Start Chat/doc4.md`
5. `Start Chat/doc5.md`

Questi 5 file contengono la documentazione del repository (esclusa `docs/tasks/`) consolidata in blocchi unici.

## 2) Workflow operativo (ordine obbligatorio)
1. Inquadrare obiettivo e dominio (Auth, Checkout, Payments, Header, FPW, Admin, Import, ecc.)
2. Applicare il protocollo governance (`docs/00-governance/ai-task-protocol.md`)
3. Compilare Start Template (`docs/templates/task-start-template.md`)
4. Verificare Acceptance Gate prima di qualunque implementazione
5. Eseguire lavoro in scope dichiarato
6. Verificare regressioni + determinismo + allineamento documentale
7. Chiudere con Closure Template (`docs/templates/task-closure-template.md`)
8. Eseguire Release Gate (`docs/50-ops/release-gate.md`) prima del deploy

## 3) Workflow Radar / Audit (pre-task)
Per finding tecnici usare il flusso ufficiale:
- `docs/00-governance/radar-analysis-workflow.md`

Regola chiave:
- Il radar non crea un sistema parallelo.
- Ogni finding va instradato nei contenitori esistenti:
  - Rischi -> `docs/00-governance/risk-register.md`
  - Bug/debito tecnico -> `docs/00-planning/core-evolution-plan.md`
  - Regole/decisioni stabili -> `docs/00-planning/decision-log.md` o ADR (`docs/60-adr/`)
  - Implementazioni concluse -> `docs/tasks/BW-TASK-XXXX-closure.md`

## 4) Principi architetturali da rispettare
- Determinismo prima di tutto
- Confini di autorita' chiari
- Nessuna deriva silenziosa
- Nessun coupling nascosto
- Flow async idempotenti e convergenti
- Aggiornamento documentazione allineato al comportamento runtime

## 5) Prompt di avvio consigliato
Quando apri una nuova chat, incolla questo blocco:

"Leggi prima `Start Chat/GO.md` e i file `Start Chat/doc1.md` ... `Start Chat/doc5.md`.
Poi avvia il task seguendo `docs/00-governance/ai-task-protocol.md`.
Prima di implementare, compila `docs/templates/task-start-template.md` e valida l'Acceptance Gate."

## 6) Nota di allineamento
Questo pacchetto e' una snapshot consolidata per bootstrap rapido.
La fonte normativa resta sempre la documentazione originale sotto `docs/`.
