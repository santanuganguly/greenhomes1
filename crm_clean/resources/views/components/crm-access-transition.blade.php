<div id="crm-dashboard-boot" class="fixed inset-0 flex items-center justify-center transition-opacity duration-300" style="z-index: 999999; background-color: #0a0a0a;">
    <canvas id="crm-dashboard-canvas" class="absolute inset-0 w-full h-full"></canvas>
    
    <div class="absolute top-4 left-4 z-10 pointer-events-none w-full">
        <div id="crm-boot-logs" class="font-mono text-[10px] md:text-xs text-[#5f7022] opacity-80 flex flex-col space-y-1"></div>
    </div>

    <div id="crm-dashboard-text" class="relative z-10 flex flex-col items-center justify-center pointer-events-none w-full px-4">
        <div id="crm-boot-typing" class="font-mono text-lg md:text-xl font-bold tracking-widest text-[#5f7022] mb-4 text-center break-all h-8"></div>
        <div id="crm-boot-status" class="font-mono text-xl md:text-2xl font-bold tracking-widest shadow-sm text-center" style="color: #0077ff; text-shadow: 0 0 8px rgba(0,119,255,0.6); height: 32px;"></div>
    </div>
</div>

<script>
(function() {
    function initDashboardBoot() {
        const overlay = document.getElementById('crm-dashboard-boot');
        const canvas = document.getElementById('crm-dashboard-canvas');
        const typingEl = document.getElementById('crm-boot-typing');
        const statusEl = document.getElementById('crm-boot-status');
        const logsEl = document.getElementById('crm-boot-logs');
        
        if (!overlay || !canvas) return;

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            overlay.remove();
            return;
        }

        // Failsafe timeout to prevent blocking (max 5.5 seconds)
        const failsafe = setTimeout(() => {
            if (overlay && document.body.contains(overlay)) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 300);
            }
        }, 5500);

        const ctx = canvas.getContext('2d', { alpha: false });
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;
        
        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        const fontSize = window.innerWidth < 768 ? 16 : 14;
        const columns = Math.floor(width / fontSize);
        const drops = Array(columns).fill(0);

        let animationFrame;
        let startTime = null;
        let typedString = "";

        const authenticLogs = [
            "[OK] Mounting virtual file system...",
            "[OK] Checking memory integrity (32GB)...",
            "[SYSTEM] Initializing AES-256 subsystem...",
            "[NET] Connecting to database cluster...",
            "[NET] Database connection established (12ms)",
            "[CORE] Loading multi-tenant configurations...",
            "[CORE] Pre-caching Livewire components...",
            "[WORKER] Starting WhatsApp background queues...",
            "[SEC] Verifying Meta Webhook signatures...",
            "[SYSTEM] Boot sequence complete."
        ];
        let currentLogIndex = 0;
        let lastLogTime = 0;

        // Status timing over 5 seconds
        const statusMessages = [
            { time: 800, text: 'INITIALISING SYSTEM...' },
            { time: 1800, text: 'LOADING CRM CORE...' },
            { time: 2800, text: 'AUTHENTICATING SESSION...' },
            { time: 3800, text: 'SECURING CONNECTION...' },
            { time: 4700, text: 'ACCESS GRANTED', color: '#a9c43d' }
        ];
        let currentStatusIndex = -1;

        function draw(timestamp) {
            if (!startTime) startTime = timestamp;
            const elapsed = timestamp - startTime;

            ctx.fillStyle = 'rgba(10, 10, 10, 0.25)';
            ctx.fillRect(0, 0, width, height);
            ctx.font = `${fontSize}px Consolas, monospace`;

            // Terminal Logs on the top left
            if (elapsed - lastLogTime > 300 && currentLogIndex < authenticLogs.length) {
                lastLogTime = elapsed;
                const p = document.createElement('div');
                p.innerText = authenticLogs[currentLogIndex];
                logsEl.appendChild(p);
                currentLogIndex++;
            }

            // Phase 1: Typing binary in the center (0 to 1200ms)
            if (elapsed < 1200) {
                if (Math.random() > 0.4) { 
                    typedString += Math.random() > 0.5 ? '1' : '0';
                    if (typedString.length > 50) typedString = typedString.substring(typedString.length - 50);
                    typingEl.innerText = typedString;
                }
            } else {
                // Phase 2: Full screen matrix
                if (typingEl.innerText !== "") {
                    typingEl.innerText = ""; 
                }
                
                for (let i = 0; i < drops.length; i++) {
                    const char = Math.random() > 0.5 ? '1' : '0';
                    const alpha = Math.random() * 0.5 + 0.1;
                    ctx.fillStyle = `rgba(0, 119, 255, ${alpha})`;
                    ctx.fillText(char, i * fontSize, drops[i] * fontSize);

                    if (drops[i] * fontSize > height && Math.random() > 0.95) {
                        drops[i] = 0;
                    } else if (Math.random() > 0.6) {
                        drops[i]++;
                    }
                }
            }

            // Update status text
            for (let i = 0; i < statusMessages.length; i++) {
                if (elapsed > statusMessages[i].time && currentStatusIndex < i) {
                    currentStatusIndex = i;
                    statusEl.innerText = statusMessages[i].text;
                    if (statusMessages[i].color) {
                        statusEl.style.color = statusMessages[i].color;
                        statusEl.style.textShadow = `0 0 8px ${statusMessages[i].color}`;
                    }
                }
            }

            // Phase 3: Dissolve at 5 seconds (5000ms onwards)
            if (elapsed > 5000) {
                overlay.style.opacity = '0';
                clearTimeout(failsafe);
                setTimeout(() => {
                    cancelAnimationFrame(animationFrame);
                    if(overlay && document.body.contains(overlay)) overlay.remove();
                }, 300);
                return;
            }

            animationFrame = requestAnimationFrame(draw);
        }

        animationFrame = requestAnimationFrame(draw);
    }

    initDashboardBoot();
})();
</script>
