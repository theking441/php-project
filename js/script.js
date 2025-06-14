// Main JavaScript for Math Friends Application

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Xử lý sự kiện cho form đăng nhập
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username === '') {
                e.preventDefault();
                showError(document.getElementById('username'), 'Vui lòng nhập tên đăng nhập');
            }
            
            if (password === '') {
                e.preventDefault();
                showError(document.getElementById('password'), 'Vui lòng nhập mật khẩu');
            }
        });
    }
    
    // Xử lý sự kiện cho form đăng ký
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();
            const grade = document.getElementById('grade').value;
            
            // Kiểm tra tên đăng nhập
            if (username === '') {
                showError(document.getElementById('username'), 'Vui lòng nhập tên đăng nhập');
                isValid = false;
            } else if (username.length < 3) {
                showError(document.getElementById('username'), 'Tên đăng nhập phải có ít nhất 3 ký tự');
                isValid = false;
            } else {
                clearError(document.getElementById('username'));
            }
            
            // Kiểm tra email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                showError(document.getElementById('email'), 'Vui lòng nhập email');
                isValid = false;
            } else if (!emailPattern.test(email)) {
                showError(document.getElementById('email'), 'Email không hợp lệ');
                isValid = false;
            } else {
                clearError(document.getElementById('email'));
            }
            
            // Kiểm tra mật khẩu
            if (password === '') {
                showError(document.getElementById('password'), 'Vui lòng nhập mật khẩu');
                isValid = false;
            } else if (password.length < 6) {
                showError(document.getElementById('password'), 'Mật khẩu phải có ít nhất 6 ký tự');
                isValid = false;
            } else {
                clearError(document.getElementById('password'));
            }
            
            // Kiểm tra xác nhận mật khẩu
            if (confirmPassword === '') {
                showError(document.getElementById('confirmPassword'), 'Vui lòng xác nhận mật khẩu');
                isValid = false;
            } else if (confirmPassword !== password) {
                showError(document.getElementById('confirmPassword'), 'Mật khẩu xác nhận không khớp');
                isValid = false;
            } else {
                clearError(document.getElementById('confirmPassword'));
            }
            
            // Kiểm tra lớp
            if (grade === '') {
                showError(document.getElementById('grade'), 'Vui lòng chọn lớp');
                isValid = false;
            } else {
                clearError(document.getElementById('grade'));
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Xử lý nút lựa chọn trong các câu hỏi kiểm tra
    const optionButtons = document.querySelectorAll('.option-btn');
    optionButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Bỏ chọn nút khác trong cùng một câu hỏi
            const questionId = this.getAttribute('data-question-id');
            const questionOptions = document.querySelectorAll(`.option-btn[data-question-id="${questionId}"]`);
            questionOptions.forEach(option => {
                option.classList.remove('selected');
            });
            
            // Đánh dấu nút này là đã chọn
            this.classList.add('selected');
            
            // Lưu câu trả lời
            const questionInput = document.getElementById(`answer_${questionId}`);
            if (questionInput) {
                questionInput.value = this.getAttribute('data-option');
            }
            
            // Chơi âm thanh
            playSelectSound();
            
            // Hiệu ứng hoạt hình
            animateOptionSelection(this);
        });
    });
    
    // Xử lý nút gửi bài kiểm tra
    const submitTestBtn = document.getElementById('submitTestBtn');
    if (submitTestBtn) {
        submitTestBtn.addEventListener('click', function() {
            // Kiểm tra xem tất cả câu hỏi đã được trả lời chưa
            const questionInputs = document.querySelectorAll('input[name^="answers["]');
            let allAnswered = true;
            
            questionInputs.forEach(input => {
                if (input.value === '') {
                    allAnswered = false;
                }
            });
            
            if (!allAnswered) {
                if (!confirm('Bạn chưa trả lời tất cả các câu hỏi. Bạn có chắc chắn muốn nộp bài?')) {
                    return;
                }
            }
            
            // Gửi form
            document.getElementById('testForm').submit();
        });
    }
    
    // Hàm hiển thị lỗi
    function showError(input, message) {
        const formGroup = input.closest('.mb-3');
        const errorElement = formGroup.querySelector('.invalid-feedback') || document.createElement('div');
        
        errorElement.className = 'invalid-feedback';
        errorElement.textContent = message;
        
        if (!formGroup.querySelector('.invalid-feedback')) {
            formGroup.appendChild(errorElement);
        }
        
        input.classList.add('is-invalid');
    }
    
    // Hàm xóa lỗi
    function clearError(input) {
        input.classList.remove('is-invalid');
        const formGroup = input.closest('.mb-3');
        const errorElement = formGroup.querySelector('.invalid-feedback');
        
        if (errorElement) {
            errorElement.textContent = '';
        }
    }
    
    // Hiệu ứng confetti cho trang kết quả
    const resultScoreElement = document.querySelector('.result-score');
    if (resultScoreElement) {
        const score = parseFloat(resultScoreElement.textContent);
        if (score >= 8) {
            showConfetti();
        }   else if (score < 6) {
            playMockingSound(); // <== GỌI HÀM ÂM THANH CHẾ NHẠO
        }
    }
});

// Hiệu ứng confetti cho kết quả xuất sắc
function showConfetti() {
    const confettiContainer = document.createElement('div');
    confettiContainer.className = 'confetti-container';
    document.body.appendChild(confettiContainer);
    
    const colors = ['#ff6b6b', '#4ecdc4', '#8a4fff', '#ffe66d', '#76e383'];
    const totalConfetti = 100;
    const pieces = [];
    
    function createPieces() {
        for (let i = 0; i < totalConfetti; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.position = 'absolute';
            piece.style.width = `${randomFromTo(8, 15)}px`;
            piece.style.height = `${randomFromTo(8, 15)}px`;
            piece.style.background = colors[Math.floor(Math.random() * colors.length)];
            piece.style.top = '-100px';
            piece.style.left = `${randomFromTo(0, 100)}vw`;
            piece.style.transform = `rotate(${randomFromTo(0, 360)}deg)`;
            piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
            
            confettiContainer.appendChild(piece);
            
            pieces.push({
                element: piece,
                x: randomFromTo(0, 100),
                y: -100,
                r: randomFromTo(0, 360),
                rotation: randomFromTo(-3, 3),
                speed: randomFromTo(2, 6)
            });
        }
    }
    
    function randomFromTo(from, to) {
        return Math.floor(Math.random() * (to - from + 1) + from);
    }
    
    function drawPieces() {
        pieces.forEach(piece => {
            piece.y += piece.speed;
            piece.r += piece.rotation;
            
            piece.element.style.top = `${piece.y}px`;
            piece.element.style.left = `${piece.x}vw`;
            piece.element.style.transform = `rotate(${piece.r}deg)`;
            
            if (piece.y > window.innerHeight) {
                piece.y = -100;
                piece.x = randomFromTo(0, 100);
            }
        });
        
        requestAnimationFrame(drawPieces);
    }
    
    createPieces();
    drawPieces();
    
    // Chơi âm thanh chúc mừng
    playSuccessSound();
    
    // Tự động xóa sau 10 giây
    setTimeout(() => {
        confettiContainer.remove();
    }, 10000);
}

// Hàm xem trước ảnh đại diện khi tải lên
function updateProfilePicture(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewImage = document.getElementById('profile-preview');
            if (previewImage) {
                previewImage.src = e.target.result;
                previewImage.classList.add('bouncing');
                
                setTimeout(() => {
                    previewImage.classList.remove('bouncing');
                }, 1000);
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Hàm phát âm thanh khi chọn đáp án
function playSelectSound() {
    // Tạo âm thanh bằng Web Audio API
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioContext.currentTime); // A5 note
    oscillator.frequency.exponentialRampToValueAtTime(
        440, audioContext.currentTime + 0.1
    ); // A4 note
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(
        0.01, audioContext.currentTime + 0.3
    );
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.3);
}

// Hàm phát âm thanh khi đạt kết quả xuất sắc
function playSuccessSound() {
    // Tạo âm thanh bằng Web Audio API
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    // Chơi chuỗi nốt nhạc vui
    const notes = [
        { note: 523.25, duration: 0.2 }, // C5
        { note: 659.25, duration: 0.2 }, // E5
        { note: 783.99, duration: 0.2 }, // G5
        { note: 1046.50, duration: 0.5 }  // C6
    ];
    
    let time = audioContext.currentTime;
    
    notes.forEach(note => {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.type = 'sine';
        oscillator.frequency.value = note.note;
        
        gainNode.gain.setValueAtTime(0.3, time);
        gainNode.gain.exponentialRampToValueAtTime(0.01, time + note.duration);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.start(time);
        oscillator.stop(time + note.duration);
        
        time += note.duration;
    });
}

// Hàm phát âm thanh chế nhạo khi điểm thấp
function playMockingSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.type = 'sawtooth';
    oscillator.frequency.setValueAtTime(200, audioContext.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(100, audioContext.currentTime + 0.5);

    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.5);
}