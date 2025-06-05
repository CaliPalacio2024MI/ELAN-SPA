@if (session('success'))
 <script>console.log("✅ Sesión success detectada: {{ session('success') }}");</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Alerts.success(@json(session('success')));
        });
    </script>
@endif

@if (session('error'))
<script>console.log("❌ No hay sesión success");</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Alerts.error(@json(session('error')));
        });
    </script>
@endif
