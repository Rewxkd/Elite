<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <aside class="side" id="side" aria-hidden="true">
        <button class="toggle" id="toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <nav class="nav">
            <a href="#" class="item"><span class="icon">⁉️</span><span class="text">...</span></a>
            <a href="#" class="item"><span class="icon">⁉️</span><span class="text">...</span></a>
            <a href="#" class="item"><span class="icon">⁉️</span><span class="text">...</span></a>
        </nav>
    </aside>

    <header class="hdr">
        <div class="hdr-box">
            <div class="logo">
                <span class="logo-txt"><img src="Elite-logo.png" alt=""></span>
            </div>
            <div class="bal">
                <span class="bal-amt">$0.00</span>
            </div>
            <div class="acts">
                <button class="btn icon" id="search" aria-label="Search">
                    <span>🔍</span>
                </button>
                <div class="group" role="group" aria-label="Profile and notifications">
                    <button class="btn icon notif" id="notif" aria-label="Notifications" aria-expanded="false">
                        <span>🔔</span>
                        <span class="badge" id="badge">3</span>
                    </button>
                    <button class="btn icon" id="prof" aria-label="Profile" aria-expanded="false">
                        <span>👤</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="cnt">
        <div class="cnt-box">
        </div>
    </main>

    <script>
        (function(){
            const btn = document.getElementById('toggle');
            const sidebar = document.getElementById('side');
            if (btn) btn.addEventListener('click', function(){
                const isOpen = document.body.classList.toggle('open');
                if (sidebar) sidebar.setAttribute('aria-hidden', (!isOpen).toString());
                btn.setAttribute('aria-expanded', isOpen.toString());
            });

            const profileBtn = document.getElementById('prof');
            const notifBtn = document.getElementById('notif');
            const notificationsPanel = document.getElementById('notifPnl');
            const notifBadge = document.getElementById('badge');

            function closeAllPanels() {
                if (notificationsPanel) notificationsPanel.setAttribute('aria-hidden', 'true');
                if (notifBtn) notifBtn.setAttribute('aria-expanded', 'false');
            }

            document.addEventListener('click', function(){ closeAllPanels(); });
        })();
    </script>
</body>
</html>