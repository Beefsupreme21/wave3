<x-layout title="Tetris">
    <body class="bg-zinc-900 min-h-screen flex items-center justify-center">
        <canvas id="game" width="800" height="600" class="bg-black border-2 border-zinc-700"></canvas>

        <script>
            const canvas = document.getElementById('game');
            const ctx = canvas.getContext('2d');

            const player = {
                x: 400,
                y: 300,
                size: 40,
                speed: 5
            };

            const keys = {};

            document.addEventListener('keydown', e => keys[e.key] = true);
            document.addEventListener('keyup', e => keys[e.key] = false);

            function update() {
                if (keys['ArrowUp'] || keys['w']) player.y -= player.speed;
                if (keys['ArrowDown'] || keys['s']) player.y += player.speed;
                if (keys['ArrowLeft'] || keys['a']) player.x -= player.speed;
                if (keys['ArrowRight'] || keys['d']) player.x += player.speed;

                player.x = Math.max(0, Math.min(canvas.width - player.size, player.x));
                player.y = Math.max(0, Math.min(canvas.height - player.size, player.y));
            }

            function draw() {
                ctx.fillStyle = '#000';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.fillStyle = '#a855f7';
                ctx.fillRect(player.x, player.y, player.size, player.size);
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
