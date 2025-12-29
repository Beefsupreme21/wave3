<x-layout title="Tetris">
    <body class="bg-zinc-900 min-h-screen flex items-center justify-center">
        <canvas id="game" width="420" height="600" class="border-2 border-zinc-700"></canvas>

        <script>
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');

            const BOARD_OFFSET = 120;

            let gameState = 'menu'; // 'menu', 'playing', 'gameover'
            let board = [];

            const colors = ['#000', '#00f0f0', '#f0f000', '#a000f0', '#00f000', '#f00000', '#0000f0', '#f0a000'];

            const pieces = [
                [[1,1,1,1]],                         // I
                [[2,2],[2,2]],                       // O
                [[0,3,0],[3,3,3]],                   // T
                [[0,4,4],[4,4,0]],                   // S
                [[5,5,0],[0,5,5]],                   // Z
                [[6,0,0],[6,6,6]],                   // J
                [[0,0,7],[7,7,7]]                    // L
            ];

            let current = { piece: null, x: 0, y: 0 };
            let heldPiece = null;
            let canHold = true;
            let dropTimer = null;

            function resetBoard() {
                board = Array(20).fill(null).map(() => Array(10).fill(0));
                heldPiece = null;
                canHold = true;
            }

            function startGame() {
                resetBoard();
                gameState = 'playing';
                spawn();
                if (dropTimer) clearInterval(dropTimer);
                dropTimer = setInterval(() => {
                    if (gameState === 'playing') {
                        drop();
                    }
                }, 500);
            }

            function gameOver() {
                gameState = 'gameover';
                if (dropTimer) clearInterval(dropTimer);
            }

            function spawn() {
                current.piece = pieces[Math.floor(Math.random() * pieces.length)];
                current.x = 3;
                current.y = 0;
                canHold = true;
                if (collides()) {
                    gameOver();
                }
            }

            function hold() {
                if (!canHold || gameState !== 'playing') return;
                canHold = false;
                if (heldPiece === null) {
                    heldPiece = current.piece;
                    spawn();
                } else {
                    const temp = current.piece;
                    current.piece = heldPiece;
                    heldPiece = temp;
                    current.x = 3;
                    current.y = 0;
                }
            }

            function collidesAt(piece, x, y) {
                for (let py = 0; py < piece.length; py++) {
                    for (let px = 0; px < piece[py].length; px++) {
                        if (piece[py][px]) {
                            const newX = x + px;
                            const newY = y + py;
                            if (newX < 0 || newX >= 10 || newY >= 20) return true;
                            if (newY >= 0 && board[newY][newX]) return true;
                        }
                    }
                }
                return false;
            }

            function collides() {
                return collidesAt(current.piece, current.x, current.y);
            }

            function getGhostY() {
                let ghostY = current.y;
                while (!collidesAt(current.piece, current.x, ghostY + 1)) {
                    ghostY++;
                }
                return ghostY;
            }

            let isHardDropping = false;

            function hardDrop() {
                if (gameState !== 'playing' || isHardDropping) return;
                isHardDropping = true;
                animateDrop();
            }

            function animateDrop() {
                if (current.y < getGhostY()) {
                    current.y++;
                    setTimeout(animateDrop, 5);
                } else {
                    isHardDropping = false;
                    lock();
                }
            }

            function lock() {
                for (let y = 0; y < current.piece.length; y++) {
                    for (let x = 0; x < current.piece[y].length; x++) {
                        if (current.piece[y][x]) {
                            board[current.y + y][current.x + x] = current.piece[y][x];
                        }
                    }
                }
                clearLines();
                spawn();
            }

            function clearLines() {
                for (let y = 19; y >= 0; y--) {
                    if (board[y].every(cell => cell !== 0)) {
                        board.splice(y, 1);
                        board.unshift(Array(10).fill(0));
                        y++;
                    }
                }
            }

            function rotateRight() {
                if (gameState !== 'playing') return;
                const rotated = current.piece[0].map((_, i) =>
                    current.piece.map(row => row[i]).reverse()
                );
                const old = current.piece;
                current.piece = rotated;
                if (collides()) current.piece = old;
            }

            function rotateLeft() {
                if (gameState !== 'playing') return;
                const rotated = current.piece[0].map((_, i) =>
                    current.piece.map(row => row[row.length - 1 - i])
                );
                const old = current.piece;
                current.piece = rotated;
                if (collides()) current.piece = old;
            }

            function move(dir) {
                if (gameState !== 'playing') return;
                current.x += dir;
                if (collides()) current.x -= dir;
            }

            function drop() {
                if (gameState !== 'playing') return;
                current.y++;
                if (collides()) {
                    current.y--;
                    lock();
                }
            }

            function drawMenu() {
                ctx.fillStyle = '#000';
                ctx.fillRect(0, 0, 420, 600);

                ctx.fillStyle = '#fff';
                ctx.font = 'bold 48px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('TETRIS', 210, 200);

                ctx.font = '20px sans-serif';
                ctx.fillStyle = '#888';
                ctx.fillText('Press ENTER to start', 210, 280);

                ctx.font = '14px sans-serif';
                ctx.fillStyle = '#666';
                ctx.fillText('← → Move', 210, 380);
                ctx.fillText('↓ Soft drop  ↑ Hard drop', 210, 400);
                ctx.fillText('Z/X Rotate  C Hold', 210, 420);
            }

            function drawGameOver() {
                // Draw the frozen board behind
                drawBoard();

                // Overlay
                ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
                ctx.fillRect(0, 0, 420, 600);

                ctx.fillStyle = '#f00';
                ctx.font = 'bold 40px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('GAME OVER', 210, 280);

                ctx.font = '20px sans-serif';
                ctx.fillStyle = '#888';
                ctx.fillText('Press ENTER to restart', 210, 340);
            }

            function drawBoard() {
                ctx.fillStyle = '#000';
                ctx.fillRect(0, 0, 420, 600);

                // Hold box
                ctx.strokeStyle = '#444';
                ctx.strokeRect(10, 10, 100, 100);
                ctx.fillStyle = '#fff';
                ctx.font = '14px sans-serif';
                ctx.textAlign = 'left';
                ctx.fillText('HOLD [C]', 20, 130);

                // Draw held piece
                if (heldPiece) {
                    for (let y = 0; y < heldPiece.length; y++) {
                        for (let x = 0; x < heldPiece[y].length; x++) {
                            if (heldPiece[y][x]) {
                                ctx.fillStyle = colors[heldPiece[y][x]];
                                ctx.fillRect(20 + x * 20, 30 + y * 20, 19, 19);
                            }
                        }
                    }
                }

                // Controls hint
                ctx.fillStyle = '#666';
                ctx.font = '11px sans-serif';
                ctx.fillText('← → Move', 15, 180);
                ctx.fillText('↓ Soft drop', 15, 195);
                ctx.fillText('↑ Hard drop', 15, 210);
                ctx.fillText('Z/X Rotate', 15, 225);

                // Draw board grid
                for (let y = 0; y < 20; y++) {
                    for (let x = 0; x < 10; x++) {
                        if (board[y][x]) {
                            ctx.fillStyle = colors[board[y][x]];
                            ctx.fillRect(BOARD_OFFSET + x * 30, y * 30, 29, 29);
                        }
                        ctx.strokeStyle = '#222';
                        ctx.strokeRect(BOARD_OFFSET + x * 30, y * 30, 30, 30);
                    }
                }

                // Draw ghost piece
                if (current.piece && gameState === 'playing') {
                    const ghostY = getGhostY();
                    for (let y = 0; y < current.piece.length; y++) {
                        for (let x = 0; x < current.piece[y].length; x++) {
                            if (current.piece[y][x]) {
                                ctx.strokeStyle = colors[current.piece[y][x]];
                                ctx.lineWidth = 2;
                                ctx.strokeRect(BOARD_OFFSET + (current.x + x) * 30 + 2, (ghostY + y) * 30 + 2, 25, 25);
                                ctx.lineWidth = 1;
                            }
                        }
                    }
                }

                // Draw current piece
                if (current.piece && gameState === 'playing') {
                    for (let y = 0; y < current.piece.length; y++) {
                        for (let x = 0; x < current.piece[y].length; x++) {
                            if (current.piece[y][x]) {
                                ctx.fillStyle = colors[current.piece[y][x]];
                                ctx.fillRect(BOARD_OFFSET + (current.x + x) * 30, (current.y + y) * 30, 29, 29);
                            }
                        }
                    }
                }
            }

            function draw() {
                if (gameState === 'menu') {
                    drawMenu();
                } else if (gameState === 'playing') {
                    drawBoard();
                } else if (gameState === 'gameover') {
                    drawGameOver();
                }
            }

            document.addEventListener('keydown', e => {
                if (gameState === 'menu' || gameState === 'gameover') {
                    if (e.key === 'Enter') {
                        startGame();
                    }
                    return;
                }

                if (e.key === 'ArrowLeft') move(-1);
                if (e.key === 'ArrowRight') move(1);
                if (e.key === 'ArrowDown') drop();
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    hardDrop();
                }
                if (e.key === 'z' || e.key === 'Z') rotateLeft();
                if (e.key === 'x' || e.key === 'X') rotateRight();
                if (e.key === 'c' || e.key === 'C') hold();
            });

            function gameLoop() {
                draw();
                requestAnimationFrame(gameLoop);
            }
            gameLoop();
        </script>
    </body>
</x-layout>
