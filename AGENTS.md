# Project Notes

- When making changes, preserve the existing Laravel/POS structure and adjust only the requested parts.
- Do not push code automatically.

## Development Direction

- Before planning or implementing POS features, read `POS_FEATURE_ROADMAP.md` and align the work with its priorities unless the user explicitly asks for a different direction.
- Implement and verify features using Laravel Blade management screens first.
- API endpoints are deferred for now. Do not make API work a blocker for completing a Blade feature unless the user explicitly requests it.
- For each feature, prioritize Blade routes, controllers, requests, services, repositories, models, views, forms, tables, validation messages, search, and actions.

## Livewire UI Guidelines

- Use Livewire for create and edit modal workflows when the modal is part of a management table: opening, closing, validation, submit, and table refresh should not reload the page.
- Use Livewire for table toggles such as active/inactive or on/off status. The action must update the database and switch UI immediately, show a loading/disabled state, and use the shared toast notification pattern.
- Use Livewire for management-page search and filter interactions when results should update without a full-page reload.
- Prefer `wire:model.live` with a short debounce for text search and immediate updates for select/date filters.
- Keep filtering, pagination, validation, and empty/loading states inside the Livewire component while preserving existing module controllers and route boundaries.
- Do not add Inertia or custom AJAX endpoints for these interactions unless explicitly requested.
- Use normal Blade forms and controller actions for delete and other workflows unless a Livewire workflow is explicitly requested. Create/edit forms should use the Livewire modal pattern when the page uses Livewire for its table.
- Keep modal inputs labeled, consistently spaced, keyboard accessible, and styled with the shared TailAdmin-inspired input/button classes.
- Use one Livewire root element per component. Any modal, toast, table, or loading state rendered by that component must remain inside the single root wrapper.
- Use the shared top-right toast for successful Livewire actions; do not add duplicate inline success alerts to individual pages.
- Do not use GET query parameters solely for Livewire search/filter state unless URL sharing or browser history is explicitly required.

## Route Collaboration Structure

- Management routes are loaded from `routes/modules/*.php` inside the authenticated `management` route group.
- Product routes belong in `routes/modules/product.php`.
- Sale routes belong in `routes/modules/sale.php`.
- Order routes belong in `routes/modules/order.php`.
- Customer routes belong in `routes/modules/customer.php`.
- Purchase routes belong in `routes/modules/purchase.php`.
- Inventory and stock routes belong in `routes/modules/inventory.php`.
- Expense routes belong in `routes/modules/expense.php`.
- Report routes belong in `routes/modules/report.php`.
- A developer must not edit another developer's module route file without coordination.
- Do not register the same route in both `web.php` and a module route file.
