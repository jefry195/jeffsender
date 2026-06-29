/**
 * scheduler-runner.cjs
 * Spawns "php artisan schedule:run" silently every 60 seconds.
 * Using windowsHide: true ensures no CMD window pops up on Windows.
 */
const { spawn } = require('child_process');
const path = require('path');

const phpPath = 'C:\\xampp\\php\\php.exe';

function runScheduler() {
    console.log(`[${new Date().toISOString()}] Running scheduler...`);
    const child = spawn(phpPath, ['artisan', 'schedule:run'], {
        cwd: __dirname,
        stdio: 'inherit',
        env: { ...process.env },
        windowsHide: true   // <-- Hides the console window completely
    });

    child.on('exit', (code) => {
        console.log(`[${new Date().toISOString()}] Scheduler finished with code ${code}. Next run in 60 seconds.`);
        setTimeout(runScheduler, 60 * 1000);
    });

    child.on('error', (err) => {
        console.error(`[${new Date().toISOString()}] Scheduler error:`, err);
        setTimeout(runScheduler, 60 * 1000);
    });
}

runScheduler();
