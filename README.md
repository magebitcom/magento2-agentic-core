# Magebit_AgenticCore

Shared commerce primitives for Magebit's agentic commerce modules. Everything here answers a question
about a Magento quote that every wire protocol has to ask, in terms none of them define.

## What belongs here

**Admission rule: if a class would have to change when a spec releases, it does not belong in this
module.** Naming a protocol is the cheapest detectable proxy for that, so the guard is a
case-insensitive grep over this whole directory for either protocol's abbreviation or either consuming
module's name. It must return nothing.

The pattern is not reproduced here on purpose: writing it out would make this file its own first
violation. It lives in the `purity_guard` function in `d/check`, which runs ahead of the linters — a
breach invalidates the point of the module, so there is no value in knowing the rest passed. Relaxing
that grep is an abort condition for the whole shared-base effort, not a fix.

A vocabulary is the usual thing that gets smuggled in. `CheckoutState` has four cases and no strings:
callers `match` over it to reach their own names, so a status renamed upstream never reaches here.
`BuyerIdentity` is the same idea for fields rather than states.

## What it provides

| Class | Answers |
| --- | --- |
| `Model\Money\MinorUnits` | What is this major-unit price in the currency's integer minor units? |
| `Model\Checkout\StateResolver` | Given a quote, an order, and whether anything blocks completion — which of the four states is this? |
| `Model\Buyer\BuyerResolver` | Who is buying, reading the customer record then the billing then the shipping address? |

## Notes on behaviour

**`MinorUnits` rounds on a decimal string**, not by multiplying. `(int) (19.99 * 100)` is `1998`,
because 19.99 has no exact binary representation. Twelve of the cases in `MinorUnitsTest` fail under
the naive implementation.

**`StateResolver` checks for an order before it checks quote state.** Placing an order deactivates the
quote, so reading `getIsActive()` first reports every completed checkout as canceled.

**`BuyerResolver` prefers billing over shipping.** Billing identifies who is paying; shipping only
names a recipient, who may be someone else. It also treats a blank string as absent, because Magento
returns `''` as readily as `null` for an unset address field and an empty string is a value these
payloads reject.
