<x-layout title="Plinko">
    <body class="bg-zinc-900 min-h-screen flex items-center justify-center">
        <canvas id="game" width="600" height="700" class="border-2 border-zinc-700"></canvas>

        <script>
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');

            const PEG_RADIUS = 8;
            const BALL_RADIUS = 12;
            const ROWS = 12;
            const GRAVITY = 0.3;
            const BOUNCE = 0.7;
            const FRICTION = 0.99;

            const pegs = [];
            const balls = [];

            const slots = [
                { label: '100', color: '#ef4444' },
                { label: '50', color: '#f97316' },
                { label: '25', color: '#eab308' },
                { label: '10', color: '#22c55e' },
                { label: '5', color: '#06b6d4' },
                { label: '10', color: '#22c55e' },
                { label: '25', color: '#eab308' },
                { label: '50', color: '#f97316' },
                { label: '100', color: '#ef4444' },
            ];

            let score = 0;

            // Generate pegs in pyramid pattern
            function initPegs() {
                pegs.length = 0;
                const startY = 100;
                const spacingY = 45;
                const spacingX = 60;

                for (let row = 0; row < ROWS; row++) {
                    const pegsInRow = row + 3;
                    const rowWidth = (pegsInRow - 1) * spacingX;
                    const startX = (canvas.width - rowWidth) / 2;

                    for (let col = 0; col < pegsInRow; col++) {
                        pegs.push({
                            x: startX + col * spacingX,
                            y: startY + row * spacingY
                        });
                    }
                }
            }

            function dropBall(x) {
                balls.push({
                    x: x,
                    y: 30,
                    vx: (Math.random() - 0.5) * 2,
                    vy: 0,
                    active: true
                });
            }

            function update() {
                for (const ball of balls) {
                    if (!ball.active) continue;

                    // Gravity
                    ball.vy += GRAVITY;

                    // Friction
                    ball.vx *= FRICTION;

                    // Move
                    ball.x += ball.vx;
                    ball.y += ball.vy;

                    // Bounce off walls
                    if (ball.x < BALL_RADIUS) {
                        ball.x = BALL_RADIUS;
                        ball.vx *= -BOUNCE;
                    }
                    if (ball.x > canvas.width - BALL_RADIUS) {
                        ball.x = canvas.width - BALL_RADIUS;
                        ball.vx *= -BOUNCE;
                    }

                    // Bounce off pegs
                    for (const peg of pegs) {
                        const dx = ball.x - peg.x;
                        const dy = ball.y - peg.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        const minDist = BALL_RADIUS + PEG_RADIUS;

                        if (dist < minDist) {
                            // Push ball out of peg
                            const angle = Math.atan2(dy, dx);
                            ball.x = peg.x + Math.cos(angle) * minDist;
                            ball.y = peg.y + Math.sin(angle) * minDist;

                            // Reflect velocity
                            const speed = Math.sqrt(ball.vx * ball.vx + ball.vy * ball.vy);
                            ball.vx = Math.cos(angle) * speed * BOUNCE + (Math.random() - 0.5) * 2;
                            ball.vy = Math.sin(angle) * speed * BOUNCE;
                        }
                    }

                    // Check if ball reached bottom
                    if (ball.y > canvas.height - 60) {
                        ball.active = false;
                        const slotWidth = canvas.width / slots.length;
                        const slotIndex = Math.floor(ball.x / slotWidth);
                        const clampedIndex = Math.max(0, Math.min(slots.length - 1, slotIndex));
                        score += parseInt(slots[clampedIndex].label);
                    }
                }

                // Remove dead balls after a bit
                for (let i = balls.length - 1; i >= 0; i--) {
                    if (!balls[i].active && balls[i].y > canvas.height) {
                        balls.splice(i, 1);
                    }
                }
            }

            function draw() {
                ctx.fillStyle = '#1a1a2e';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Draw slots at bottom
                const slotWidth = canvas.width / slots.length;
                for (let i = 0; i < slots.length; i++) {
                    ctx.fillStyle = slots[i].color;
                    ctx.fillRect(i * slotWidth, canvas.height - 50, slotWidth - 2, 50);
                    ctx.fillStyle = '#fff';
                    ctx.font = 'bold 16px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText(slots[i].label, i * slotWidth + slotWidth / 2, canvas.height - 20);
                }

                // Draw pegs
                ctx.fillStyle = '#fff';
                for (const peg of pegs) {
                    ctx.beginPath();
                    ctx.arc(peg.x, peg.y, PEG_RADIUS, 0, Math.PI * 2);
                    ctx.fill();
                }

                // Draw balls
                ctx.fillStyle = '#f59e0b';
                for (const ball of balls) {
                    ctx.beginPath();
                    ctx.arc(ball.x, ball.y, BALL_RADIUS, 0, Math.PI * 2);
                    ctx.fill();
                }

                // Draw score
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 24px sans-serif';
                ctx.textAlign = 'left';
                ctx.fillText('Score: ' + score, 20, 35);

                // Instructions
                ctx.font = '14px sans-serif';
                ctx.fillStyle = '#888';
                ctx.textAlign = 'right';
                ctx.fillText('Click to drop ball', canvas.width - 20, 35);
            }

            canvas.addEventListener('click', (e) => {
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                dropBall(x);
            });

            initPegs();

            function gameLoop() {
                update();
                draw();
                requestAnimationFrame(gameLoop);
            }
            gameLoop();
        </script>
    </body>
</x-layout>

