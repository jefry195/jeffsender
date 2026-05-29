import fs from 'fs'

const compareAndFilter = (array1, array2) => {
    return array1.filter((item) => {
        return array2.includes(item)
    })
}

const isUrlValid = (url) => {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

const fileExists = (path) => {
    return Boolean(fs.existsSync(path))
}

const deleteFile = async (path) => {
    return new Promise((resolve, reject) => {
        fs.unlink(path, (err) => {
            err ? reject(err) : resolve(true)
        })
    })
}

export { compareAndFilter, isUrlValid, fileExists, deleteFile }
