# Workflow Rules (wpblackwork)

## Mandatory checks after code changes
- After every PHP code change, run `php -l` on each modified PHP file.
- After every task that modifies PHP, run `composer run lint:main`.
- Report check results in the final response.

## Notes
- If a check cannot run, explicitly state why.
