<?php if (session_status() == PHP_SESSION_NONE)session_start();?>

<!-- Bootstrap JS -->
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <!-- Custom JS -->
 <script src="/findywearce/public/js/main.js"></script>

 <script>
    // Navbar scroll effect
    window.addEventListner('scroll', function() {
        const navbar = document.getElementById('mainNavbarr');
        if (window.scrollY > 50){
            navbar.style.background = 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
        }
        else {
             navbar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                navbar.style.boxShadow = 'none';
        }
    });
 </script>
 <?php include __DIR__ . '/chatbot.php'; ?>
 <?php if (isset($_SESSION['user_id'])): ?>
<script>
// Fetch notifications every 30 seconds
fetchNotifications();
setInterval(fetchNotifications, 30000);

function fetchNotifications() {
    fetch('/findywearce/api/notifications.php')
    .then(res => res.json())
    .then(data => {
        const count   = data.count;
        const notifs  = data.notifications;
        const badge   = document.getElementById('notifCount');
        const list    = document.getElementById('notifList');
        const unread  = document.getElementById('notifUnread');

        // Update badge
        if (count > 0) {
            badge.style.display = 'flex';
            badge.textContent   = count;
        } else {
            badge.style.display = 'none';
        }

        if (unread) {
            unread.textContent = count > 0 ? count + ' unread' : 'All read';
        }

        // Update list
        if (list && notifs.length > 0) {
            list.innerHTML = notifs.map(n => `
                <li>
                    <a class="dropdown-item py-2 ${!n.is_read ? 'bg-light' : ''}"
                        href="#"
                        onclick="markRead(${n.id})">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center
                                justify-content-center flex-shrink-0"
                                style="width:35px;height:35px;
                                background:linear-gradient(135deg,#667eea,#764ba2);">
                                <i class="fas fa-bell text-white"
                                    style="font-size:0.7rem;"></i>
                            </div>
                            <div>
                                <p class="mb-0 small">${n.message}</p>
                                <small class="text-muted">${timeAgo(n.created_at)}</small>
                            </div>
                            ${!n.is_read ? '<span class="ms-auto"><i class="fas fa-circle text-primary" style="font-size:0.4rem;"></i></span>' : ''}
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
            `).join('');
        } else if (list) {
            list.innerHTML = `
                <li class="px-3 py-3 text-center text-muted">
                    <i class="fas fa-bell-slash mb-2 d-block"></i>
                    No notifications
                </li>`;
        }
    })
    .catch(err => console.log('Notification error:', err));
}

function markRead(id) {
    fetch('/findywearce/api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'mark_read=1&notif_id=' + id
    }).then(() => fetchNotifications());
}

function markAllRead() {
    fetch('/findywearce/api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'mark_read=1&notif_id=0'
    }).then(() => fetchNotifications());
}

function timeAgo(datetime) {
    const now  = new Date();
    const then = new Date(datetime);
    const diff = Math.floor((now - then) / 1000);

    if (diff < 60)     return 'Just now';
    if (diff < 3600)   return Math.floor(diff/60) + ' min ago';
    if (diff < 86400)  return Math.floor(diff/3600) + ' hrs ago';
    return Math.floor(diff/86400) + ' days ago';
}
</script>
<?php endif; ?>
 </body>
 </html>