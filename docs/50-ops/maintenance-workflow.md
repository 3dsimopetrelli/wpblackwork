# Maintenance Workflow

## Official Sequence
1. Identify domain (`feature` / `integration` / `architecture`).
2. Read related documentation before touching code.
3. Define impact scope (files, runtime surfaces, dependent integrations).
4. Implement the fix/refactor/patch.
5. Run the regression protocol.
6. Update documentation and memory:
   - `CHANGELOG.md`
   - Relevant domain `README.md`
   - ADR if architectural decision changed

## Mandatory Rule
No direct fix is allowed without first reading related documentation for the impacted domain.

## Output Expectation
Each maintenance task should leave:
- a clear implementation delta,
- validated runtime behavior,
- synchronized documentation state.
