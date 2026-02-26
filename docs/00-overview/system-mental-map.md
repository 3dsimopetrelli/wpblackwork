# System Mental Map

This document defines the navigation logic of the project as a cognitive system, not only as a file tree.

Blackwork documentation is organized around 5 core layers that explain how the project thinks, operates, and evolves.

## 1) Identity & Vision
This layer defines why the project exists, what it protects, and where it is going.

Primary references:
- [Project Identity](project-identity.md)
- [Cultural Manifesto](cultural-manifesto.md)
- [Business Model](business-model.md)

## 2) Architecture
This layer describes structural foundations, core modules, and technical contracts.

Reference:
- [10-architecture](../10-architecture/README.md)

## 3) Functional Domains
This layer maps product behavior and user-facing capabilities by domain (checkout, header, smart header, my account, product types, etc.).

Reference:
- [30-features](../30-features/README.md)

## 4) External Integrations
This layer covers integrations with third-party systems (payments, auth, Brevo, Supabase).

Reference:
- [40-integrations](../40-integrations/README.md)

## 5) Operations & Memory
This layer preserves operational practices and project memory.

References:
- [50-ops](../50-ops/README.md)
- [99-archive](../99-archive/README.md)

## System Boundary Clarification
- The WordPress plugin powers the SHOP domain only (commerce workflows, account flows, integrations tied to web sales).
- The App (Electron + Web App) is a separate ecosystem with its own architecture and product logic.
- Documentation is the shared cognitive infrastructure that keeps both present implementation and future expansion coherent.

## How to Use This Map
1. Start from identity if the question is strategic.
2. Move to architecture for structural decisions.
3. Move to features/integrations for implementation details.
4. Use ops/archive for maintenance context and historical continuity.

This mental map is the reference model for understanding where decisions belong.
