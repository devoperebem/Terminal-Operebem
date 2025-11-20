(function () {
    var layout = document.querySelector('.tv-layout');
    if (!layout) {
        return;
    }

    var refreshAttr = layout.getAttribute('data-refresh-ms');
    var refreshMs = parseInt(refreshAttr, 10);
    if (!Number.isNaN(refreshMs) && refreshMs > 0) {
        setTimeout(function () {
            window.location.reload();
        }, refreshMs);
    }

    var player = document.getElementById('tv-player');
    var placeholder = document.getElementById('tv-placeholder');
    var channelLabel = document.getElementById('current-channel');
    var watchLink = document.getElementById('watch-link');
    var playerStatus = document.getElementById('player-status');
    var buttons = document.querySelectorAll('.channel-list .channel');

    if (!buttons.length) {
        return;
    }

    Array.prototype.forEach.call(buttons, function (button) {
        var videoId = button.getAttribute('data-video-id');
        if (!videoId) {
            return;
        }

        button.addEventListener('click', function () {
            var targetSrc = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
            if (player) {
                if (player.src !== targetSrc) {
                    player.src = targetSrc;
                }
                player.classList.add('is-visible');
            }

            if (placeholder) {
                placeholder.classList.add('is-hidden');
            }

            if (channelLabel) {
                channelLabel.textContent = button.getAttribute('data-name') || 'Canal selecionado';
            }

            if (playerStatus) {
                playerStatus.textContent = 'Ao vivo';
            }

            if (watchLink) {
                var watchUrl = button.getAttribute('data-watch-url');
                if (watchUrl) {
                    watchLink.href = watchUrl;
                }
                watchLink.classList.remove('is-hidden');
            }

            Array.prototype.forEach.call(buttons, function (btn) {
                btn.classList.remove('active');
            });
            button.classList.add('active');
        });
    });
})();
