<x-layout title="RPG">
    <body class="bg-zinc-900 min-h-screen flex items-center justify-center">
        <canvas id="game" width="800" height="600" class="border-2 border-zinc-700"></canvas>

        <script>
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');

            // Define actual different rooms
            const rooms = {
                house: {
                    background: '#2d2d44',
                    playerSpawn: { x: 100, y: 300 },
                    walls: [
                        { x: 0, y: 0, w: 800, h: 40 },
                        { x: 0, y: 560, w: 800, h: 40 },
                        { x: 0, y: 0, w: 40, h: 600 },
                        { x: 760, y: 0, w: 40, h: 250 },
                        { x: 760, y: 350, w: 40, h: 250 },
                    ],
                    doors: [
                        { x: 760, y: 250, w: 40, h: 100, leadsTo: 'forest', color: '#8b4513' }
                    ],
                    objects: [
                        { x: 200, y: 150, w: 120, h: 80, color: '#654321', label: 'Table' },
                        { x: 500, y: 400, w: 80, h: 100, color: '#4a4a6a', label: 'Chest' },
                    ]
                },
                forest: {
                    background: '#1a3d1a',
                    playerSpawn: { x: 100, y: 300 },
                    walls: [
                        { x: 0, y: 0, w: 800, h: 40 },
                        { x: 0, y: 560, w: 800, h: 40 },
                        { x: 0, y: 0, w: 40, h: 250 },
                        { x: 0, y: 350, w: 40, h: 250 },
                        { x: 760, y: 0, w: 40, h: 600 },
                    ],
                    doors: [
                        { x: 0, y: 250, w: 40, h: 100, leadsTo: 'house', color: '#8b4513' }
                    ],
                    objects: [
                        { x: 300, y: 100, w: 60, h: 80, color: '#228b22', label: 'Tree' },
                        { x: 500, y: 200, w: 60, h: 80, color: '#228b22', label: 'Tree' },
                        { x: 400, y: 400, w: 60, h: 80, color: '#228b22', label: 'Tree' },
                        { x: 600, y: 450, w: 100, h: 60, color: '#3a5f3a', label: 'Bush' },
                    ]
                }
            };

            let currentRoom = 'house';

            const player = {
                x: 100,
                y: 300,
                size: 32,
                speed: 4,
                color: '#22c55e'
            };

            const keys = {};

            document.addEventListener('keydown', e => keys[e.key] = true);
            document.addEventListener('keyup', e => keys[e.key] = false);

            function collides(a, b) {
                return a.x < b.x + b.w &&
                       a.x + a.size > b.x &&
                       a.y < b.y + b.h &&
                       a.y + a.size > b.y;
            }

            function update() {
                const room = rooms[currentRoom];
                let newX = player.x;
                let newY = player.y;

                if (keys['ArrowUp'] || keys['w']) newY -= player.speed;
                if (keys['ArrowDown'] || keys['s']) newY += player.speed;
                if (keys['ArrowLeft'] || keys['a']) newX -= player.speed;
                if (keys['ArrowRight'] || keys['d']) newX += player.speed;

                // Check wall collisions
                let canMove = true;
                for (const wall of room.walls) {
                    if (collides({ x: newX, y: newY, size: player.size }, wall)) {
                        canMove = false;
                        break;
                    }
                }

                // Check object collisions
                for (const obj of room.objects) {
                    if (collides({ x: newX, y: newY, size: player.size }, { x: obj.x, y: obj.y, w: obj.w, h: obj.h })) {
                        canMove = false;
                        break;
                    }
                }

                if (canMove) {
                    player.x = newX;
                    player.y = newY;
                }

                // Check door collisions
                for (const door of room.doors) {
                    if (collides(player, door)) {
                        currentRoom = door.leadsTo;
                        const newRoom = rooms[currentRoom];
                        player.x = newRoom.playerSpawn.x;
                        player.y = newRoom.playerSpawn.y;
                        break;
                    }
                }
            }

            function draw() {
                const room = rooms[currentRoom];

                // Background
                ctx.fillStyle = room.background;
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Walls
                ctx.fillStyle = '#1a1a1a';
                for (const wall of room.walls) {
                    ctx.fillRect(wall.x, wall.y, wall.w, wall.h);
                }

                // Doors
                for (const door of room.doors) {
                    ctx.fillStyle = door.color;
                    ctx.fillRect(door.x, door.y, door.w, door.h);
                }

                // Objects
                for (const obj of room.objects) {
                    ctx.fillStyle = obj.color;
                    ctx.fillRect(obj.x, obj.y, obj.w, obj.h);
                }

                // Player
                ctx.fillStyle = player.color;
                ctx.fillRect(player.x, player.y, player.size, player.size);

                // Room name
                ctx.fillStyle = '#fff';
                ctx.font = '16px sans-serif';
                ctx.fillText(currentRoom.toUpperCase(), 60, 25);
            }

            function gameLoop() {
                update();
                draw();
                requestAnimationFrame(gameLoop);
            }

            gameLoop();
        </script>
    </body>
</x-layout>
