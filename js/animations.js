// Animations for Math Friends Application

document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
});

function initializeAnimations() {
    if (document.querySelector('.hero-section')) {
        animateHomePage();
    } else if (document.querySelector('.result-animation')) {
        animateResultsPage();
    } else if (document.querySelector('.question-card')) {
        animateTestQuestions();
    }

    const badgeElements = document.querySelectorAll('.badge-img');
    badgeElements.forEach(badge => {
        badge.addEventListener('mouseover', function() {
            this.classList.add('pulsing');
        });

        badge.addEventListener('mouseout', function() {
            this.classList.remove('pulsing');
        });
    });

    const characterElements = document.querySelectorAll('.character');
    characterElements.forEach(character => {
        const animations = ['floating', 'bouncing', 'pulsing'];
        const randomAnimation = animations[Math.floor(Math.random() * animations.length)];
        character.classList.add(randomAnimation);
    });
}

function animateHomePage() {
    const heroImage = document.querySelector('.hero-image');
    if (heroImage) {
        heroImage.classList.add('floating');
        addDecorativeElements();
    }

    const buttons = document.querySelectorAll('.hero-buttons .btn');
    buttons.forEach((btn, index) => {
        setTimeout(() => {
            btn.style.opacity = '0';
            btn.style.transform = 'translateY(20px)';
            btn.style.transition = 'all 0.5s ease';
            setTimeout(() => {
                btn.style.opacity = '1';
                btn.style.transform = 'translateY(0)';
            }, 100);
        }, index * 200);
    });
}

function addDecorativeElements() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;

    const decorCount = 15;
    const decorTypes = ['star', 'circle', 'triangle', 'square'];
    const decorColors = ['#FF6B6B', '#4ECDC4', '#8A4FFF', '#FFD166', '#76E383'];

    for (let i = 0; i < decorCount; i++) {
        const decorElement = document.createElement('div');
        const decorType = decorTypes[Math.floor(Math.random() * decorTypes.length)];
        const decorColor = decorColors[Math.floor(Math.random() * decorColors.length)];

        decorElement.className = `decoration ${decorType}`;
        decorElement.style.position = 'absolute';
        decorElement.style.top = `${Math.random() * 100}%`;
        decorElement.style.left = `${Math.random() * 100}%`;
        decorElement.style.color = decorColor;
        decorElement.style.opacity = '0.6';
        decorElement.style.zIndex = '-1';

        if (decorType === 'star') {
            decorElement.innerHTML = '★';
            decorElement.style.fontSize = `${Math.random() * 20 + 10}px`;
        } else if (decorType === 'circle') {
            decorElement.style.width = `${Math.random() * 20 + 10}px`;
            decorElement.style.height = decorElement.style.width;
            decorElement.style.borderRadius = '50%';
            decorElement.style.backgroundColor = decorColor;
        } else if (decorType === 'triangle') {
            decorElement.style.width = '0';
            decorElement.style.height = '0';
            decorElement.style.borderLeft = `${Math.random() * 10 + 5}px solid transparent`;
            decorElement.style.borderRight = `${Math.random() * 10 + 5}px solid transparent`;
            decorElement.style.borderBottom = `${Math.random() * 20 + 10}px solid ${decorColor}`;
        } else if (decorType === 'square') {
            decorElement.style.width = `${Math.random() * 15 + 5}px`;
            decorElement.style.height = decorElement.style.width;
            decorElement.style.backgroundColor = decorColor;
            decorElement.style.transform = `rotate(${Math.random() * 45}deg)`;
        }

        const animations = ['floating', 'bouncing', 'pulsing'];
        const randomAnimation = animations[Math.floor(Math.random() * animations.length)];
        decorElement.classList.add(randomAnimation);

        decorElement.style.animationDuration = `${Math.random() * 5 + 2}s`;

        heroSection.appendChild(decorElement);
    }
}

function animateResultsPage() {
    const resultScore = document.querySelector('.result-score');
    if (resultScore) {
        const targetScore = parseFloat(resultScore.textContent);
        let currentScore = 0;
        resultScore.textContent = '0';

        const interval = setInterval(() => {
            currentScore += 0.1;
            if (currentScore > targetScore) {
                currentScore = targetScore;
                clearInterval(interval);
                resultScore.classList.add('bouncing');
                showCongratulation(targetScore);
            }
            resultScore.textContent = currentScore.toFixed(1);
        }, 30);

        const newBadge = document.querySelector('.new-badge');
        if (newBadge) {
            setTimeout(() => {
                animateBadgeEarned(newBadge);
            }, 1500);
        }
    }
}

function showCongratulation(score) {
    const resultMessage = document.querySelector('.result-message');
    if (resultMessage) {
        let congratsMessage = '';
        if (score == 10) {
            congratsMessage = 'Xuất sắc! Bạn là một thiên tài toán học! 🌟';
        } else if (score >= 8) {
            congratsMessage = 'Tuyệt vời! Bạn làm rất tốt! 🎉';
        } else if (score >= 6) {
            congratsMessage = 'Khá tốt! Hãy tiếp tục cố gắng! 👍';
        } else {
            congratsMessage = 'Ôi không! Hãy luyện tập thêm nhé! 😢';
            // 👉 Thêm đoạn phát âm thanh khi điểm ≤ 6
    const audio = new Audio('sounds/sad_sound.mp3');
    audio.play();
        }

        resultMessage.textContent = congratsMessage;
        resultMessage.classList.add('pulsing');

        const characterContainer = document.createElement('div');
        characterContainer.className = 'character-container text-center mt-4';

        let characterImage = '';
        if (score >= 8) {
            characterImage = 'happy_character.svg';
        } else if (score >= 6) {
            characterImage = 'smile_character.svg';
        } else {
            characterImage = 'sad_character.svg'; // 👈 Đã đổi thành sad_character.svg nếu điểm < 6
        }

        characterContainer.innerHTML = `
            <img src="images/${characterImage}" alt="Character" class="character bouncing" height="150">
        `;
        resultMessage.parentNode.insertBefore(characterContainer, resultMessage.nextSibling);
    }
}
