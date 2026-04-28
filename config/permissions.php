<?php

return [
    /**
     * عند تفعيله: أي Route داخل Middleware الصلاحيات لازم يكون له mapping في config/route-permissions.php
     * مفيد لضمان "صلاحية لكل فانكشن/أكشن" وعدم نسيان أي مسار بدون حماية.
     */
    'enforce_route_map' => (bool) env('PERMISSIONS_ENFORCE_ROUTE_MAP', false),
];

