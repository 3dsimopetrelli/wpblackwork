# Blackwork Release Gate

## Purpose
The Release Gate is the final operational validation stage before production deployment.

It ensures that:
- runtime stability
- governance compliance
- rollback readiness

are explicitly verified before shipping.

## When it runs
The Release Gate runs after task closure and before deployment.

## Mandatory Checks

1. PHP syntax check

Run `php -l` on all modified PHP files.

2. Project lint

Run `composer run lint:main`.

3. Checkout smoke test

Verify:
- checkout loads
- payment method selector works
- place order button works

4. Webhook replay safety

Verify duplicate webhook delivery does not mutate order state twice.

5. Redirect engine sanity

Verify protected routes are not redirectable.

6. Admin settings save flow

Verify admin settings save successfully with valid nonce and fail with invalid nonce.

7. Media library smoke test

Verify media modal and admin media list still function.

8. Documentation alignment

Verify:
- risk register updated if needed
- task closure artifact present
- docs reflect behavior changes

9. Rollback readiness

Verify a revert path is documented.

10. Task lifecycle compliance

Verify:
- Task Start Template exists
- Task Closure Template exists
- determinism verification is present
