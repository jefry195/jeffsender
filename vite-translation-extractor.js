import fs from 'fs'
import path from 'path'

export function translationExtractor(options = {}) {
  const {
    outputDir = './lang',
    defaultLocale = 'en',
    sourceDir = './resources/js',
    extensions = ['.vue', '.js', '.ts'],
    preserveExisting = true
  } = options

  let extractedKeys = new Set()

  // Recursive file finder
  function getAllFiles(dirPath, arrayOfFiles = []) {
    const files = fs.readdirSync(dirPath)

    files.forEach((file) => {
      const fullPath = path.join(dirPath, file)
      if (fs.statSync(fullPath).isDirectory()) {
        arrayOfFiles = getAllFiles(fullPath, arrayOfFiles)
      } else if (extensions.some((ext) => file.endsWith(ext))) {
        arrayOfFiles.push(fullPath)
      }
    })

    return arrayOfFiles
  }

  function extractTranslationKeys() {
    extractedKeys.clear()

    if (!fs.existsSync(sourceDir)) {
      console.warn(`Source directory ${sourceDir} does not exist`)
      return
    }

    const files = getAllFiles(sourceDir)

    files.forEach((file) => {
      const content = fs.readFileSync(file, 'utf8')

      // Multiple patterns for different usage scenarios
      const patterns = [
        /trans\(['"`]([^'"`]+)['"`]\)/g,
        /\{\{\s*trans\(['"`]([^'"`]+)['"`]\)\s*\}\}/g,
        /v-text="trans\(['"`]([^'"`]+)['"`]\)"/g,
        /:[\w-]+="trans\(['"`]([^'"`]+)['"`]\)"/g
      ]

      patterns.forEach((pattern) => {
        let match
        while ((match = pattern.exec(content)) !== null) {
          extractedKeys.add(match[1])
        }
        pattern.lastIndex = 0
      })
    })

    generateTranslationFile()
  }

  function generateTranslationFile() {
    const outputPath = path.join(outputDir, `${defaultLocale}.json`)
    let existingTranslations = {}

    if (preserveExisting && fs.existsSync(outputPath)) {
      try {
        existingTranslations = JSON.parse(fs.readFileSync(outputPath, 'utf8'))
      } catch (e) {
        console.warn('Could not parse existing translation file')
      }
    }

    const translations = { ...existingTranslations }

    extractedKeys.forEach((key) => {
      if (!translations.hasOwnProperty(key)) {
        translations[key] = key
      }
    })

    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true })
    }

    fs.writeFileSync(outputPath, JSON.stringify(translations, null, 2))

    console.log(`✅ Updated ${outputPath} with ${extractedKeys.size} translation keys`)
  }

  return {
    name: 'translation-extractor',
    buildStart() {
      extractTranslationKeys()
    },

    handleHotUpdate({ file }) {
      if (file.includes('/resources/js/')) {
        setTimeout(() => extractTranslationKeys(), 100)
      }
    }
  }
}