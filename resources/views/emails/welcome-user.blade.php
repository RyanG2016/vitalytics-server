<x-mail::message>
{{-- Logo/Header --}}
<div style="text-align: center; margin-bottom: 30px;">
<img src="{{ config('app.url') }}/favicon.svg" alt="Vitalytics" width="48" height="48" style="display: inline-block;">
<div style="font-size: 24px; font-weight: bold; color: #0F766E; margin-top: 8px;">Vitalytics</div>
<div style="font-size: 12px; color: #6B7280; letter-spacing: 1px;">MONITORING DASHBOARD</div>
</div>

# Welcome, {{ $user->name }}!

Your Vitalytics account has been created. You now have access to monitor your applications' health and analytics data.

---

## Your Login Credentials

<x-mail::panel>
**Email:** {{ $user->email }}

**Temporary Password:** `{{ $password }}`

**Dashboard URL:** [{{ $loginUrl }}]({{ $loginUrl }})
</x-mail::panel>

---

## Account Details

<x-mail::table>
| Setting | Value |
|:--------|:------|
| **Role** | {{ $roleName }} |
@if(count($dashboardAccess) > 0)
| **Dashboard Access** | {{ implode(' & ', $dashboardAccess) }} |
@else
| **Dashboard Access** | All Dashboards |
@endif
@if(count($productNames) > 0)
| **Product Access** | {{ implode(', ', $productNames) }} |
@else
| **Product Access** | All Products |
@endif
</x-mail::table>

<x-mail::button :url="$loginUrl" color="success">
Login to Dashboard
</x-mail::button>

---

<x-mail::subcopy>
**Security Notice:** Please change your password immediately after your first login. If you did not request this account, please contact your administrator.
</x-mail::subcopy>

Thanks,<br>
**The {{ config('app.name') }} Team**
</x-mail::message>
