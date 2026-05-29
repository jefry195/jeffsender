module.exports = {
  apps: [
    {
      name: 'whatsapp-server',
      cwd: './whatsapp-server',
      script: 'app.js',
      node_args: '--no-warnings',
      max_memory_restart: '400M',
      exp_backoff_restart_delay: 100,
      env: {
        NODE_ENV: 'production'
      }
    },
    {
      name: 'laravel-server',
      cwd: './',
      script: 'artisan',
      interpreter: 'C:\\xampp\\php\\php.exe',
      args: 'serve --port=8010',
      max_memory_restart: '200M',
      exp_backoff_restart_delay: 100
    },
    {
      name: 'laravel-queue',
      cwd: './',
      script: 'artisan',
      interpreter: 'C:\\xampp\\php\\php.exe',
      args: 'queue:work --tries=1 --sleep=3 --memory=128 --timeout=60',
      max_memory_restart: '150M',
      exp_backoff_restart_delay: 100
    }
  ]
};
