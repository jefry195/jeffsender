import laravel from 'laravel-vite-plugin'
import path from 'path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { translationExtractor } from '../../vite-translation-extractor.js'
const moduleName = path.basename(__dirname)

export default defineConfig({
  build: {
    outDir: 'public/build-modules/' + moduleName,
    emptyOutDir: true,
    manifest: 'manifest.json'
  },
  plugins: [
    laravel({
      publicDirectory: '../../public',
      buildDirectory: 'build-modules/' + moduleName,
      input: [
        __dirname + '/resources/js/app.js',
        'resources/css/app.css',
        'resources/scss/admin/main.scss'
      ],
      refresh: true
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false
        }
      }
    }),
    translationExtractor({
      outputDir: path.resolve(__dirname, './../../lang'),
      defaultLocale: 'en',
      sourceDir: path.resolve(__dirname, '../WhatsappWeb/resources/js/'),
      filePattern: '**/*.{vue,js,ts}',
      preserveExisting: true
    })
  ],
  resolve: {
    alias: {
      '@modules': path.resolve(__dirname, '../'),
      '@root': path.resolve(__dirname, './../../resources/js/'),
      '@whatsappWeb': path.resolve(__dirname, '../WhatsappWeb/resources/js/'),
      '@whatsapp': path.resolve(__dirname, '../Whatsapp/resources/js/')
    }
  }
})