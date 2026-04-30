// FavoriteButton component
const favoriteApiUrl = new URL('../../api/favorites.php', document.currentScript.src).toString();
class FavoriteButton {
    constructor(gameType, gameName, container) {
        this.gameType = gameType.toLowerCase();
        this.gameName = gameName;
        this.container = container;
        this.isFavorited = false;
        this.init();
    }

    async init() {
        await this.checkFavoriteStatus();
        this.render();
        this.attachEvents();
    }

    async checkFavoriteStatus() {
        try {
            const response = await fetch(favoriteApiUrl, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                this.isFavorited = data.favorites.some(fav => fav.game_type && fav.game_type.toLowerCase() === this.gameType);
            }
        } catch (error) {
            console.error('Error checking favorite status:', error);
        }
    }

    async toggleFavorite() {
        const method = this.isFavorited ? 'DELETE' : 'POST';
        try {
            const response = await fetch(favoriteApiUrl, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ game_type: this.gameType })
            });
            const data = await response.json();
            if (data.success) {
                this.isFavorited = !this.isFavorited;
                this.updateIcon();
            } else {
                console.error('Failed to toggle favorite:', data.message);
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
        }
    }

    render() {
        this.container.innerHTML = `
            <button class="favorite-btn" id="fav-btn-${this.gameType}" aria-label="Toggle favorite">
                <svg class="star-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                </svg>
            </button>
        `;
        this.updateIcon();
    }

    updateIcon() {
        const btn = this.container.querySelector('.favorite-btn');
        const icon = btn.querySelector('.star-icon');
        if (this.isFavorited) {
            icon.style.fill = '#FFD700';
            icon.style.stroke = '#FFD700';
            btn.classList.add('favorited');
        } else {
            icon.style.fill = 'none';
            icon.style.stroke = 'rgba(255,255,255,0.5)';
            btn.classList.remove('favorited');
        }
    }

    attachEvents() {
        const btn = this.container.querySelector('.favorite-btn');
        btn.addEventListener('click', () => this.toggleFavorite());
    }
}

// Usage: new FavoriteButton(gameId, gameName, containerElement);
