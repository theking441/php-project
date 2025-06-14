// Lớp TestTimer - Quản lý thời gian làm bài kiểm tra

class TestTimer {
    constructor(timerElement, timeLimitMinutes, onTimeUp) {
        this.timerElement = timerElement;
        this.timeLimitMinutes = timeLimitMinutes;
        this.onTimeUp = onTimeUp;
        this.totalSeconds = timeLimitMinutes * 60;
        this.remainingSeconds = this.totalSeconds;
        this.startTime = null;
        this.pauseTime = null;
        this.interval = null;
        this.isRunning = false;
    }
    
    // Bắt đầu bộ đếm thời gian
    start() {
        if (this.isRunning) return;
        
        this.startTime = new Date();
        this.isRunning = true;
        
        // Đặt lại thời gian nếu đã hết
        if (this.remainingSeconds <= 0) {
            this.remainingSeconds = this.totalSeconds;
        }
        
        this.updateDisplay();
        
        this.interval = setInterval(() => {
            this.updateTimer();
            
            if (this.remainingSeconds <= 0) {
                this.stop();
                if (typeof this.onTimeUp === 'function') {
                    this.onTimeUp();
                }
            }
        }, 1000);
    }
    
    // Dừng bộ đếm thời gian
    stop() {
        if (!this.isRunning) return;
        
        clearInterval(this.interval);
        this.interval = null;
        this.isRunning = false;
    }
    
    // Tạm dừng bộ đếm thời gian
    pause() {
        if (!this.isRunning) return;
        
        this.stop();
        this.pauseTime = new Date();
    }
    
    // Tiếp tục bộ đếm thời gian sau khi tạm dừng
    resume() {
        if (this.isRunning || !this.pauseTime) return;
        
        const pauseDuration = (new Date() - this.pauseTime) / 1000;
        this.startTime = new Date(this.startTime.getTime() + pauseDuration * 1000);
        this.pauseTime = null;
        this.start();
    }
    
    // Đặt lại bộ đếm thời gian
    reset() {
        this.stop();
        this.remainingSeconds = this.totalSeconds;
        this.startTime = null;
        this.pauseTime = null;
        this.updateDisplay();
    }
    
    // Cập nhật bộ đếm thời gian
    updateTimer() {
        const elapsedSeconds = this.getElapsedSeconds();
        this.remainingSeconds = this.totalSeconds - elapsedSeconds;
        
        // Đảm bảo không âm
        if (this.remainingSeconds < 0) {
            this.remainingSeconds = 0;
        }
        
        this.updateDisplay();
    }
    
    // Cập nhật hiển thị thời gian
    updateDisplay() {
        if (!this.timerElement) return;
        
        const minutes = Math.floor(this.remainingSeconds / 60);
        const seconds = this.remainingSeconds % 60;
        
        // Hiển thị định dạng MM:SS
        this.timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Thay đổi màu khi sắp hết thời gian
        if (this.remainingSeconds <= 60) {
            this.timerElement.classList.add('timer-danger');
        } else if (this.remainingSeconds <= 120) {
            this.timerElement.classList.add('timer-warning');
            this.timerElement.classList.remove('timer-danger');
        } else {
            this.timerElement.classList.remove('timer-warning', 'timer-danger');
        }
    }
    
    // Lấy số giây đã trôi qua
    getElapsedSeconds() {
        if (!this.startTime) return 0;
        return Math.floor((new Date() - this.startTime) / 1000);
    }
    
    // Lấy số giây còn lại
    getRemainingSeconds() {
        return this.remainingSeconds;
    }
}

// Khởi tạo đồng hồ đếm ngược khi tải trang
document.addEventListener('DOMContentLoaded', function() {
    const timerElement = document.getElementById('test-timer');
    if (timerElement) {
        const timeLimitMinutes = parseInt(timerElement.getAttribute('data-time-limit')) || 10;
        
        // Hàm xử lý khi hết thời gian
        const handleTimeUp = function() {
            alert('Đã hết thời gian làm bài! Bài kiểm tra sẽ được nộp tự động.');
            document.getElementById('testForm').submit();
        };
        
        // Khởi tạo bộ đếm thời gian
        const timer = new TestTimer(timerElement, timeLimitMinutes, handleTimeUp);
        
        // Lưu bộ đếm thời gian trong window để có thể truy cập từ nơi khác
        window.testTimer = timer;
        
        // Bắt đầu bộ đếm thời gian
        timer.start();
        
        // Nút tạm dừng/tiếp tục
        const pauseButton = document.getElementById('pause-timer');
        if (pauseButton) {
            pauseButton.addEventListener('click', function() {
                if (timer.isRunning) {
                    timer.pause();
                    this.innerHTML = '<i class="fas fa-play"></i> Tiếp tục';
                } else {
                    timer.resume();
                    this.innerHTML = '<i class="fas fa-pause"></i> Tạm dừng';
                }
            });
        }
    }
});