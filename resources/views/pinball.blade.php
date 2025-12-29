<x-layout title="Pinball">
    <body class="bg-zinc-900 min-h-screen flex items-center justify-center">
        <canvas id="game" width="400" height="700" class="border-2 border-zinc-700"></canvas>

        <script>
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');

            const GRAVITY = 0.15;
            const FRICTION = 0.99;
            const BOUNCE = 0.8;

            let gameState = 'playing';
            let score = 0;

            const ball = {
                x: 350,
                y: 300,
                vx: -3,
                vy: 0,
                radius: 10
            };

            // Flippers positioned with gutters on outside
            const flippers = {
                left: { x: 120, y: 620, angle: 0.4, length: 60, active: false },
                right: { x: 280, y: 620, angle: Math.PI - 0.4, length: 60, active: false }
            };

            const bumpers = [
                { x: 200, y: 150, radius: 30, points: 100 },
                { x: 100, y: 220, radius: 25, points: 50 },
                { x: 300, y: 220, radius: 25, points: 50 },
                { x: 150, y: 320, radius: 20, points: 50 },
                { x: 250, y: 320, radius: 20, points: 50 },
                { x: 200, y: 420, radius: 20, points: 25 },
            ];

            // Walls that create the gutter shape
            const walls = [
                // Left outer wall down to gutter entrance
                { x1: 10, y1: 0, x2: 10, y2: 520 },
                // Left gutter angled entrance
                { x1: 10, y1: 520, x2: 50, y2: 580 },
                // Left gutter inner wall (short)
                { x1: 50, y1: 580, x2: 50, y2: 620 },
                
                // Right outer wall down to gutter entrance
                { x1: 390, y1: 0, x2: 390, y2: 520 },
                // Right gutter angled entrance
                { x1: 390, y1: 520, x2: 350, y2: 580 },
                // Right gutter inner wall (short)
                { x1: 350, y1: 580, x2: 350, y2: 620 },

                // Center divider walls above flippers
                { x1: 90, y1: 560, x2: 90, y2: 600 },
                { x1: 310, y1: 560, x2: 310, y2: 600 },
            ];

            // Gutter zones - if ball enters, it's lost
            const gutterLeft = { x: 0, y: 620, w: 50, h: 100 };
            const gutterRight = { x: 350, y: 620, w: 50, h: 100 };
            const gutterMiddle = { x: 170, y: 650, w: 60, h: 100 };

            function resetBall() {
                ball.x = 350;
                ball.y = 300;
                ball.vx = -3 + (Math.random() - 0.5) * 2;
                ball.vy = 0;
                gameState = 'playing';
            }

            function circleCollision(ball, bumper) {
                const dx = ball.x - bumper.x;
                const dy = ball.y - bumper.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                return dist < ball.radius + bumper.radius;
            }

            function hitBumper(bumper) {
                const dx = ball.x - bumper.x;
                const dy = ball.y - bumper.y;
                const angle = Math.atan2(dy, dx);

                ball.x = bumper.x + Math.cos(angle) * (ball.radius + bumper.radius + 1);
                ball.y = bumper.y + Math.sin(angle) * (ball.radius + bumper.radius + 1);

                const speed = Math.sqrt(ball.vx * ball.vx + ball.vy * ball.vy);
                const newSpeed = Math.max(speed, 8) * 1.2;
                ball.vx = Math.cos(angle) * newSpeed;
                ball.vy = Math.sin(angle) * newSpeed;

                score += bumper.points;
            }

            function lineCircleCollision(x1, y1, x2, y2) {
                const dx = x2 - x1;
                const dy = y2 - y1;
                const len = Math.sqrt(dx * dx + dy * dy);
                if (len === 0) return false;
                const nx = dx / len;
                const ny = dy / len;

                const px = ball.x - x1;
                const py = ball.y - y1;

                const proj = px * nx + py * ny;
                if (proj < 0 || proj > len) return false;

                const closestX = x1 + nx * proj;
                const closestY = y1 + ny * proj;

                const distX = ball.x - closestX;
                const distY = ball.y - closestY;
                const dist = Math.sqrt(distX * distX + distY * distY);

                if (dist < ball.radius + 4) {
                    const perpX = distX / dist;
                    const perpY = distY / dist;

                    ball.x = closestX + perpX * (ball.radius + 5);
                    ball.y = closestY + perpY * (ball.radius + 5);

                    const dot = ball.vx * perpX + ball.vy * perpY;
                    ball.vx = (ball.vx - 2 * dot * perpX) * BOUNCE;
                    ball.vy = (ball.vy - 2 * dot * perpY) * BOUNCE;

                    return true;
                }
                return false;
            }

            function hitFlipper(flipper, side) {
                const flipAngle = flipper.active ? (side === 'left' ? -0.5 : Math.PI + 0.5) : flipper.angle;
                const endX = flipper.x + Math.cos(flipAngle) * flipper.length;
                const endY = flipper.y + Math.sin(flipAngle) * flipper.length;

                const dx = endX - flipper.x;
                const dy = endY - flipper.y;
                const len = Math.sqrt(dx * dx + dy * dy);
                const nx = dx / len;
                const ny = dy / len;

                const px = ball.x - flipper.x;
                const py = ball.y - flipper.y;

                const proj = px * nx + py * ny;
                if (proj < -10 || proj > len + 10) return false;

                const closestX = flipper.x + nx * Math.max(0, Math.min(len, proj));
                const closestY = flipper.y + ny * Math.max(0, Math.min(len, proj));

                const distX = ball.x - closestX;
                const distY = ball.y - closestY;
                const dist = Math.sqrt(distX * distX + distY * distY);

                if (dist < ball.radius + 6) {
                    const perpX = distX / dist;
                    const perpY = distY / dist;

                    ball.x = closestX + perpX * (ball.radius + 7);
                    ball.y = closestY + perpY * (ball.radius + 7);

                    if (flipper.active) {
                        ball.vy = -12;
                        ball.vx += (side === 'left' ? 5 : -5);
                    } else {
                        const dot = ball.vx * perpX + ball.vy * perpY;
                        ball.vx = (ball.vx - 2 * dot * perpX) * BOUNCE;
                        ball.vy = (ball.vy - 2 * dot * perpY) * BOUNCE;
                    }
                    return true;
                }
                return false;
            }

            function inGutter() {
                // Left gutter
                if (ball.x < 50 && ball.y > 620) return true;
                // Right gutter
                if (ball.x > 350 && ball.y > 620) return true;
                // Middle
                if (ball.x > 170 && ball.x < 230 && ball.y > 660) return true;
                return false;
            }

            function update() {
                if (gameState !== 'playing') return;

                ball.vy += GRAVITY;
                ball.vx *= FRICTION;
                ball.x += ball.vx;
                ball.y += ball.vy;

                // Top wall
                if (ball.y < ball.radius) {
                    ball.y = ball.radius;
                    ball.vy *= -BOUNCE;
                }

                // Wall collisions
                for (const wall of walls) {
                    lineCircleCollision(wall.x1, wall.y1, wall.x2, wall.y2);
                }

                // Bumper collisions
                for (const bumper of bumpers) {
                    if (circleCollision(ball, bumper)) {
                        hitBumper(bumper);
                    }
                }

                // Flipper collisions
                hitFlipper(flippers.left, 'left');
                hitFlipper(flippers.right, 'right');

                // Check gutters
                if (inGutter() || ball.y > canvas.height + 50) {
                    gameState = 'gameover';
                }
            }

            function drawFlipper(flipper, side) {
                const flipAngle = flipper.active ? (side === 'left' ? -0.5 : Math.PI + 0.5) : flipper.angle;

                ctx.save();
                ctx.translate(flipper.x, flipper.y);
                ctx.rotate(flipAngle);

                ctx.fillStyle = '#f59e0b';
                ctx.beginPath();
                ctx.roundRect(0, -6, flipper.length, 12, 6);
                ctx.fill();

                ctx.restore();
            }

            function draw() {
                ctx.fillStyle = '#1a1a2e';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Gutter zones (darker)
                ctx.fillStyle = '#0f0f1a';
                ctx.fillRect(0, 580, 50, 120);
                ctx.fillRect(350, 580, 50, 120);
                ctx.fillRect(170, 640, 60, 60);

                // Walls
                ctx.strokeStyle = '#666';
                ctx.lineWidth = 8;
                ctx.lineCap = 'round';
                for (const wall of walls) {
                    ctx.beginPath();
                    ctx.moveTo(wall.x1, wall.y1);
                    ctx.lineTo(wall.x2, wall.y2);
                    ctx.stroke();
                }

                // Bumpers
                for (const bumper of bumpers) {
                    ctx.fillStyle = '#ef4444';
                    ctx.beginPath();
                    ctx.arc(bumper.x, bumper.y, bumper.radius, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.strokeStyle = '#fff';
                    ctx.lineWidth = 3;
                    ctx.stroke();
                }

                // Flippers
                drawFlipper(flippers.left, 'left');
                drawFlipper(flippers.right, 'right');

                // Ball
                ctx.fillStyle = '#c0c0c0';
                ctx.beginPath();
                ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
                ctx.fill();

                // Score
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 24px sans-serif';
                ctx.textAlign = 'left';
                ctx.fillText('Score: ' + score, 20, 35);

                // Controls
                ctx.font = '12px sans-serif';
                ctx.fillStyle = '#666';
                ctx.fillText('Z = Left   X = Right', 20, 55);

                // Game over
                if (gameState === 'gameover') {
                    ctx.fillStyle = 'rgba(0,0,0,0.7)';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);

                    ctx.fillStyle = '#fff';
                    ctx.font = 'bold 36px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText('GAME OVER', 200, 320);
                    ctx.font = '18px sans-serif';
                    ctx.fillText('Press ENTER to restart', 200, 360);
                }
            }

            document.addEventListener('keydown', e => {
                if (e.key === 'z' || e.key === 'Z') flippers.left.active = true;
                if (e.key === 'x' || e.key === 'X') flippers.right.active = true;
                if (e.key === 'Enter' && gameState === 'gameover') {
                    score = 0;
                    resetBall();
                }
            });

            document.addEventListener('keyup', e => {
                if (e.key === 'z' || e.key === 'Z') flippers.left.active = false;
                if (e.key === 'x' || e.key === 'X') flippers.right.active = false;
            });

            function gameLoop() {
                update();
                draw();
                requestAnimationFrame(gameLoop);
            }
            gameLoop();
        </script>
    </body>
</x-layout>
