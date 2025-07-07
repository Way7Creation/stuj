
/**
 * NewsManager - Управляет страницей новостей и социальных сигналов.
 */
class NewsManager {
    constructor() {
        this.newsContainer = document.getElementById('news-container');
        this.socialContainer = document.getElementById('social-container');
        this.newsLoading = document.getElementById('news-loading');
        this.socialLoading = document.getElementById('social-loading');
        this.refreshInterval = 60000; // 1 минута
        this.socket = null;

        this.initialize();
    }

    initialize() {
        this.loadInitialData();
        this.initWebSocket();
        setInterval(() => this.loadInitialData(), this.refreshInterval);
    }

    initWebSocket() {
        if (typeof io === 'undefined') return;
        this.socket = io();
        this.socket.on('connect', () => console.log('🔌 WebSocket на странице новостей подключен'));
        this.socket.on('news_update', (data) => this.prependNewsItem(data.data));
        this.socket.on('social_signal', (data) => this.prependSocialItem(data.data));
    }

    async loadInitialData() {
        this.setLoading(true);
        try {
            const [newsResponse, socialResponse] = await Promise.all([
                fetch('/api/news/latest'),
                fetch('/api/social/signals')
            ]);
            
            if (!newsResponse.ok) throw new Error('News API failed');
            const newsData = await newsResponse.json();
            this.renderNews(newsData.news || []);
            
            if (!socialResponse.ok) throw new Error('Social API failed');
            const socialData = await socialResponse.json();
            this.renderSocialSignals(socialData.signals || []);

        } catch (error) {
            console.error('Ошибка загрузки начальных данных:', error);
            this.newsContainer.innerHTML = `<div class="alert alert-danger">Ошибка загрузки новостей.</div>`;
            this.socialContainer.innerHTML = `<div class="alert alert-danger">Ошибка загрузки сигналов.</div>`;
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(isLoading) {
        if (isLoading) {
            this.newsLoading.style.display = 'block';
            this.socialLoading.style.display = 'block';
            this.newsContainer.style.display = 'none';
            this.socialContainer.style.display = 'none';
        } else {
            this.newsLoading.style.display = 'none';
            this.socialLoading.style.display = 'none';
            this.newsContainer.style.display = 'block';
            this.socialContainer.style.display = 'block';
        }
    }

    renderNews(newsItems) {
        if (!newsItems || newsItems.length === 0) {
            this.newsContainer.innerHTML = '<div class="text-center text-muted p-5">Нет доступных новостей</div>';
            return;
        }
        this.newsContainer.innerHTML = newsItems.map(item => this.createNewsItemHtml(item)).join('');
    }

    renderSocialSignals(signals) {
        if (!signals || signals.length === 0) {
            this.socialContainer.innerHTML = '<div class="text-center text-muted p-5">Нет доступных социальных сигналов</div>';
            return;
        }
        this.socialContainer.innerHTML = signals.map(signal => this.createSocialItemHtml(signal)).join('');
    }

    prependNewsItem(item) {
        if(this.newsContainer.querySelector('.text-muted')) this.newsContainer.innerHTML = '';
        const itemHtml = this.createNewsItemHtml(item);
        this.newsContainer.insertAdjacentHTML('afterbegin', itemHtml);
        const firstItem = this.newsContainer.firstElementChild;
        firstItem.classList.add('new-item-highlight');
        setTimeout(() => firstItem.classList.remove('new-item-highlight'), 3000);
    }
    
    prependSocialItem(signal) {
        if(this.socialContainer.querySelector('.text-muted')) this.socialContainer.innerHTML = '';
        const itemHtml = this.createSocialItemHtml(signal);
        this.socialContainer.insertAdjacentHTML('afterbegin', itemHtml);
        const firstItem = this.socialContainer.firstElementChild;
        firstItem.classList.add('new-item-highlight');
        setTimeout(() => firstItem.classList.remove('new-item-highlight'), 3000);
    }

    createNewsItemHtml(item) {
        return `
            <div class="card news-item mb-3">
             <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="card-title mb-1">${this.escapeHtml(item.title)}</h6>
                    <span class="badge ${this.getSentimentBadgeClass(item.sentiment_score)}">
                        ${this.getSentimentLabel(item.sentiment_score)}
                    </span>
                </div>
                <p class="text-muted small mb-2">
                    ${item.source} • ${this.formatDate(item.published_at)}
                    ${item.impact_score ? `• Impact: ${this.renderImpactScore(item.impact_score)}` : ''}
                </p>
                ${item.summary ? `<p class="card-text small">${this.escapeHtml(item.summary)}</p>` : ''}
                ${item.affected_coins && item.affected_coins.length > 0 ? `
                    <div class="affected-coins mt-2">
                        ${item.affected_coins.map(coin => `<span class="badge bg-info mx-1">${coin}</span>`).join('')}
                    </div>` : ''}
                ${item.url ? `<a href="${item.url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary mt-2">Read more</a>` : ''}
                </div>
            </div>`;
    }

    createSocialItemHtml(signal) {
         return `
            <div class="card social-signal mb-3 ${signal.influence_score > 7 ? 'border-warning' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-${this.getPlatformBadgeClass(signal.platform)}">${signal.platform}</span>
                            ${signal.is_verified_author ? '<i class="fas fa-check-circle text-primary ms-2" title="Verified Author"></i>' : ''}
                        </div>
                        <span class="badge ${this.getSentimentBadgeClass(signal.sentiment)}">${this.getSentimentLabel(signal.sentiment)}</span>
                    </div>
                    <p class="card-text my-2">${this.escapeHtml(signal.content)}</p>
                    <div class="signal-meta text-muted small">
                        ${signal.author ? `<span>@${signal.author}</span>` : ''}
                        ${signal.author_followers ? `<span class="ms-2">${this.formatNumber(signal.author_followers)} followers</span>` : ''}
                        ${signal.influence_score ? `<span class="ms-2">Influence: ${signal.influence_score.toFixed(1)}/10</span>` : ''}
                        <span class="ms-2">${this.formatDate(signal.created_at)}</span>
                    </div>
                    ${signal.mentioned_coins && signal.mentioned_coins.length > 0 ? `
                        <div class="mentioned-coins mt-2">
                            ${signal.mentioned_coins.map(coin => `<span class="badge bg-secondary mx-1">${coin}</span>`).join('')}
                        </div>` : ''}
                </div>
            </div>`;
    }

    getSentimentBadgeClass(sentiment) {
        if (sentiment > 0.3) return 'bg-success';
        if (sentiment < -0.3) return 'bg-danger';
        return 'bg-secondary';
    }

    getSentimentLabel(sentiment) {
        if (sentiment > 0.3) return 'Positive';
        if (sentiment < -0.3) return 'Negative';
        return 'Neutral';
    }
    
    getPlatformBadgeClass(platform) {
        const classes = { 'twitter': 'info', 'reddit': 'warning', 'telegram': 'primary' };
        return classes[platform.toLowerCase()] || 'secondary';
    }

    renderImpactScore(score) {
        const stars = '★'.repeat(Math.round(score / 2)) + '☆'.repeat(5 - Math.round(score / 2));
        return `<span class="impact-score">${stars}</span>`;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMins = Math.floor((now - date) / 60000);
        if (diffMins < 1) return `just now`;
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
        return date.toLocaleDateString();
    }
    
    formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.newsManager = new NewsManager();
});
