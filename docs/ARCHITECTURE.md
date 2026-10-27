# FurnishWyz ERP — Architecture Notes

Laravel 12 · PHP 8.2+ · MySQL/MariaDB · Blade + Bootstrap 5 (via Vite) · spatie/laravel-permission.

## Where things live

| Concern | Location |
|---|---|
| Business rules (stock, money, documents) | `app/Services/*Service.php` |
| Decimal-safe arithmetic | `app/Support/Money.php` (bcmath, never floats) |
| Line pricing / tax (inclusive & exclusive) | `app/Support/LineCalculator.php` |
| Permission names + default roles | `app/Support/Permissions.php` (seeded by `RolesAndPermissionsSeeder`) |
| Record-level / branch authorization | `app/Policies/*` |
| Module-level authorization | each controller's `middleware()` (`can:<permission>`) |
| Sidebar | `config/navigation.php` (permission-aware) |
| Business settings | `settings` table via `SettingsService` (defaults in `SettingsService::DEFAULTS`) |
| Shared layout | `resources/views/layouts/*` |
| Form fields | `resources/views/components/form/*` (`<x-form.input>` etc.) |

## Rules that must not be bypassed

1. **Stock changes only through `InventoryService::move()`**, inside the caller's DB transaction.
   It locks the product row, writes a `stock_movements` ledger row and updates the cached
   `stock_levels` row together. Never write `stock_levels` directly.
2. **Money is calculated server-side.** Controllers pass quantities/prices to services; the
   services compute totals with `Money`/`LineCalculator`. Submitted totals are never trusted.
   A submitted `unit_price` on sales is honoured only with `sales.override-price`.
3. **Payments go through `PaymentService::record()`**, which locks the document, rejects
   overpayment/over-refund, posts a `financial_transactions` row and refreshes `payment_status`.
4. **Retries are safe:** sales and payments accept an `idempotency_key`; purchase receipt and
   adjustment approval check state under a row lock, so a repeated request cannot move stock twice.
5. **Branch isolation:** branch-owned models use `BelongsToBranch` — list queries call
   `->visibleTo($user)`, policies call `$user->canAccessBranch()`. New records get their branch
   from `Controller::resolveBranchId()`, never from an unchecked form field.
6. **Totals/status columns are not mass assignable** on documents; services set them with `forceFill`.
7. Financial documents are never deleted: sales are cancelled, purchases cancelled or returned.

## Data model summary

- Catalogue: `categories` (tree), `brands`, `units`, `taxes`, `products` (JSON `specifications`
  for any product type), optional `product_variants` (JSON `options`, stock tracked per variant).
- Inventory: `stock_levels` (cache), `stock_movements` (ledger), `stock_adjustments` (+items, approval flow).
- Purchasing: `purchases` → `purchase_items` (ordered/received/returned qty) → `purchase_returns`.
- Sales: `sales` → `sale_items` (price, cost snapshot, tax) → `sale_returns`.
- Money: `payments` (morph to Sale/Purchase, direction in/out), `expenses`, `cash_registers`,
  `financial_transactions` (ledger), `number_sequences` (document numbers).
- `audit_logs`: automatic create/update/delete via the `Auditable` trait, plus login/logout.

Balance due on a document = `grand_total − returned_amount − paid_amount`.

## Designing a page later

Each page extends `layouts.app`, sets `@section('title')`, passes `breadcrumbs`, and may use
`@section('page-actions')` and `@push('scripts')`. Pages currently contain structural markup
only; restyle freely, but keep form field names (they match the FormRequests) and keep
authorization checks (`@can`) in place — hidden buttons are never the security boundary.
