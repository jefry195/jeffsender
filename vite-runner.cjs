/**
 * vite-runner.cjs
 * Spawns "npm run dev" as a child process that inherits stdout/stderr
 * so PM2 can capture the output without opening a CMD window.
 */
const { spawn } = require('child_process');
const path = require('path');

const npmCmd = process.platform === 'win32' ? 'npm.cmd' : 'npm';

const child = spawn(npmCmd, ['run', 'dev'], {
    cwd: __dirname,
    stdio: 'inherit',
    env: { ...process.env },
    windowsHide: true,  // <-- prevents CMD window from appearing on Windows
    shell: true
});

child.on('exit', (code) => {
    process.exit(code ?? 0);
});

process.on('SIGINT',  () => child.kill('SIGINT'));
process.on('SIGTERM', () => child.kill('SIGTERM'));
