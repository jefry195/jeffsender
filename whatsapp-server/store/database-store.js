import {
    BufferJSON,
    jidNormalizedUser,
    toNumber,
} from 'baileys'

import logger from '../utils/logger.js'
import prisma from '../utils/prisma.js'

export default (sessionId) => {
    const bind = (ev) => {
        ev.on('chats.set', async ({ chats }) => {
            logger.info('chats.set event received, count:', chats.length)
            for (const chat of chats) {
                await upsertChat(chat)
            }
        })

        ev.on('chats.upsert', async (chats) => {
            logger.info('chats.upsert event received, count:', chats.length)
            for (const chat of chats) {
                await upsertChat(chat)
            }
        })

        ev.on('chats.update', async (updates) => {
            logger.info('chats.update event received, count:', updates.length)
            for (const update of updates) {
                await updateChat(update)
            }
        })

        ev.on('contacts.set', async ({ contacts }) => {
            for (const contact of contacts) {
                await upsertContact(contact)
            }
        })

        ev.on('contacts.upsert', async (contacts) => {
            for (const contact of contacts) {
                await upsertContact(contact)
            }
        })

        ev.on('contacts.update', async (updates) => {
            for (const update of updates) {
                await updateContact(update)
            }
        })

        ev.on('messages.upsert', async ({ messages, type }) => {
            logger.info('messages.upsert event received, count:', messages.length, 'type:', type)
            for (const msg of messages) {
                await upsertMessage(msg)
            }
        })

        ev.on('messages.set', async ({ messages, isLatest }) => {
            logger.info('messages.set event received, count:', messages.length, 'isLatest:', isLatest)
            for (const msg of messages) {
                await upsertMessage(msg)
            }
        })

        ev.on('messages.update', async (updates) => {
            for (const { key, update } of updates) {
                await updateMessage(key, update)
            }
        })
    }

    const upsertChat = async (chat) => {
        try {
            logger.info('Upserting chat:', chat.id)
            await prisma.chat.upsert({
                where: { sessionId_id: { sessionId, id: chat.id } },
                create: {
                    sessionId,
                    id: chat.id,
                    conversationTimestamp: toNumber(chat.conversationTimestamp),
                    unreadCount: chat.unreadCount,
                    name: chat.name,
                    notSpam: chat.notSpam,
                    archived: chat.archived,
                    pinned: chat.pinned,
                    muteEndTime: toNumber(chat.muteEndTime),
                    lastUpdated: BigInt(Date.now())
                },
                update: {
                    conversationTimestamp: toNumber(chat.conversationTimestamp),
                    unreadCount: chat.unreadCount,
                    name: chat.name,
                    notSpam: chat.notSpam,
                    archived: chat.archived,
                    pinned: chat.pinned,
                    muteEndTime: toNumber(chat.muteEndTime),
                    lastUpdated: BigInt(Date.now())
                }
            })
            logger.info('Chat saved successfully:', chat.id)
        } catch (error) {
            logger.info('DB Store - upsertChat', error)
        }
    }

    const updateChat = async (update) => {
        try {
            logger.info('Updating chat:', update.id)

            // Extract only valid Chat fields
            const validFields = {
                conversationTimestamp: update.conversationTimestamp ? toNumber(update.conversationTimestamp) : undefined,
                unreadCount: update.unreadCount,
                readOnly: update.readOnly,
                endOfHistoryTransfer: update.endOfHistoryTransfer,
                ephemeralExpiration: update.ephemeralExpiration,
                ephemeralSettingTimestamp: update.ephemeralSettingTimestamp ? toNumber(update.ephemeralSettingTimestamp) : undefined,
                disappearingMode: update.disappearingMode,
                lastMsgTimestamp: update.lastMsgTimestamp ? toNumber(update.lastMsgTimestamp) : undefined,
                name: update.name,
                notSpam: update.notSpam,
                archived: update.archived,
                pinned: update.pinned,
                muteEndTime: update.muteEndTime ? toNumber(update.muteEndTime) : undefined,
                lastUpdated: BigInt(Date.now())
            }

            // Remove undefined values
            Object.keys(validFields).forEach(key => validFields[key] === undefined && delete validFields[key])

            let updateId = update.id

            // check if updateId is lid then resolve it from update.messages[0].message.key.remoteJid/remoteJidAlt (where includes @s.whatsapp.net)
            if (updateId.endsWith('@lid')) {
                logger.debug('Resolving lid:', updateId)

                let key = update.messages?.[0]?.message?.key

                // check if update.messages[0].message.key.remoteJid or update.messages[0].message.key.remoteJidAlt includes @s.whatsapp.net
                if (key?.remoteJid?.includes('@s.whatsapp.net')) {
                    updateId = key?.remoteJid
                    logger.debug('Resolved lid to remoteJid:', updateId)
                } else if (key?.remoteJidAlt?.includes('@s.whatsapp.net')) {
                    updateId = key?.remoteJidAlt
                    logger.debug('Resolved lid to remoteJidAlt:', updateId)
                } else {
                    logger.debug('Failed to resolve lid mapping:', {
                        updateId,
                        messages: update.messages,
                        remoteJid: update.messages?.[0]?.message?.key?.remoteJid,
                        remoteJidAlt: update.messages?.[0]?.message?.key?.remoteJidAlt
                    })
                }
            }

            // Use upsert instead of update to handle cases where chat doesn't exist yet
            await prisma.chat.upsert({
                where: { sessionId_id: { sessionId, id: updateId } },
                create: {
                    sessionId,
                    id: updateId,
                    conversationTimestamp: update.conversationTimestamp ? toNumber(update.conversationTimestamp) : null,
                    unreadCount: update.unreadCount || 0,
                    name: update.name,
                    notSpam: update.notSpam,
                    archived: update.archived,
                    pinned: update.pinned,
                    muteEndTime: update.muteEndTime ? toNumber(update.muteEndTime) : null,
                    lastUpdated: BigInt(Date.now())
                },
                update: validFields
            })
            logger.info('Chat updated/created successfully:', update.id)
        } catch (error) {
            logger.error('Error updating chat:', error)
        }
    }

    const upsertContact = async (contact) => {
        try {
            logger.info('Upserting contact:', JSON.stringify(contact))
            await prisma.contact.upsert({
                where: { sessionId_id: { sessionId, id: contact.id } },
                create: {
                    sessionId,
                    id: contact.id,
                    name: contact.name,
                    notify: contact.notify,
                    verifiedName: contact.verifiedName,
                    imgUrl: contact.imgUrl,
                    status: contact.status
                },
                update: {
                    name: contact.name,
                    notify: contact.notify,
                    verifiedName: contact.verifiedName,
                    imgUrl: contact.imgUrl,
                    status: contact.status
                }
            })
        } catch (error) {
            logger.info('DB Store - upsertContact', error)
        }
    }

    const updateContact = async (update) => {
        try {
            logger.info('Updating contact:', JSON.stringify(update))
            // Extract only valid Contact fields
            const validFields = {
                name: update.name,
                notify: update.notify,
                verifiedName: update.verifiedName,
                imgUrl: update.imgUrl,
                status: update.status
            }

            // Remove undefined values
            Object.keys(validFields).forEach(key => validFields[key] === undefined && delete validFields[key])

            // Use upsert instead of update to handle cases where contact doesn't exist yet
            await prisma.contact.upsert({
                where: { sessionId_id: { sessionId, id: update.id } },
                create: {
                    sessionId,
                    id: update.id,
                    name: update.name,
                    notify: update.notify,
                    verifiedName: update.verifiedName,
                    imgUrl: update.imgUrl,
                    status: update.status
                },
                update: validFields
            })
            logger.info('Contact updated/created successfully:', update.id)
        } catch (error) {
            logger.error('Error updating contact:', error)
        }
    }

    const upsertMessage = async (msg) => {
        try {
            // Skip protocol messages - they're not real chat messages
            const messageType = Object.keys(msg.message || {})[0]
            const protocolMessagesToSkip = [
                'protocolMessage',
                'senderKeyDistributionMessage',
                'messageContextInfo'
            ]

            // If it's a protocol message, check if it's a system message we should skip
            if (protocolMessagesToSkip.includes(messageType)) {
                const protocolType = msg.message?.protocolMessage?.type
                const skipTypes = [
                    'HISTORY_SYNC_NOTIFICATION',
                    'APP_STATE_SYNC_KEY_SHARE',
                    'INITIAL_SECURITY_NOTIFICATION_SETTING_SYNC',
                    'APP_STATE_FATAL_EXCEPTION_NOTIFICATION'
                ]

                if (skipTypes.includes(protocolType)) {
                    logger.debug('Skipping protocol message:', protocolType)
                    return
                }
            }

            logger.debug('Upserting message:', {
                messageId: msg.key.id,
                remoteJid: msg.key.remoteJid
            })

            let remoteJid = jidNormalizedUser(msg.key.remoteJid)

            // check if remoteJid contains @lid 
            if (remoteJid.includes('@lid')) {
                logger.debug('RemoteJid contains @lid:', remoteJid)
                // now try to get jid from remoteJidAlt if contains @s.whatsapp.net
                let remoteJidAlt = msg.key.remoteJidAlt || ''
                if (remoteJidAlt.includes('@s.whatsapp.net')) {
                    remoteJid = remoteJidAlt
                    logger.debug('RemoteJid resolved from remoteJidAlt:', remoteJid)
                }
            }

            await prisma.message.upsert({
                where: {
                    sessionId_remoteJid_id: {
                        sessionId,
                        remoteJid,
                        id: msg.key.id
                    }
                },
                create: {
                    sessionId,
                    remoteJid,
                    id: msg.key.id,
                    agentId: msg.key.agentId,
                    chatId: msg.key.remoteJid,
                    fromMe: msg.key.fromMe,
                    pushName: msg.pushName,
                    broadcast: msg.broadcast,
                    message: JSON.stringify(msg.message, BufferJSON.replacer),
                    messageType: Object.keys(msg.message || {})[0],
                    messageTimestamp: toNumber(msg.messageTimestamp),
                    participant: msg.key.participant,
                    status: msg.status ? String(msg.status) : null
                },
                update: {
                    status: msg.status ? String(msg.status) : null,
                    message: JSON.stringify(msg.message, BufferJSON.replacer)
                }
            })
            logger.debug('Message saved successfully:', {
                messageId: msg.key.id,
                remoteJid: msg.key.remoteJid
            })
        } catch (error) {
            logger.error('Error upserting message:', error.message)
        }
    }

    const updateMessage = async (key, update) => {
        logger.debug('Updating message:', {
            data: update
        })
        try {
            let remoteJid = jidNormalizedUser(key.remoteJid)

            // Fix for LID and device-specific JIDs: Message might be stored with phone JID (from upsert resolution)
            // If we have an LID or device-specific JID (contains ':'), check if the message exists with a different JID in DB
            if (remoteJid.includes('@lid') || remoteJid.includes(':')) {
                const storedMessage = await prisma.message.findFirst({
                    where: {
                        sessionId,
                        id: key.id
                    },
                    select: { remoteJid: true }
                })

                if (storedMessage) {
                    remoteJid = storedMessage.remoteJid
                    logger.debug('Resolved lid/device-specific jid to stored remoteJid:', remoteJid)
                }
            }

            await prisma.message.update({
                where: {
                    sessionId_remoteJid_id: {
                        sessionId,
                        remoteJid,
                        id: key.id
                    }
                },
                data: {
                    status: update.status ? String(update.status) : null,
                }
            })
            logger.debug('Message updated successfully:', key.id)
        } catch (error) {
            logger.error('Error updating message:', error.message)
        }
    }

    const loadMessages = async (jid, count, cursor) => {
        try {
            // Check if count is actually a message ID (string and not a number)
            // This is used by getMessage in whatsapp.js
            if (count && typeof count === 'string' && isNaN(Number(count))) {
                const messageId = count
                logger.debug('Loading specific message:', { jid, messageId })

                let msg = await prisma.message.findFirst({
                    where: {
                        sessionId,
                        remoteJid: jid,
                        id: messageId
                    }
                })

                // If not found and jid contains @lid or has device suffix (e.g., :83@s.whatsapp.net), 
                // try to find by ID only
                if (!msg && (jid.includes('@lid') || jid.includes(':'))) {
                    logger.debug('Message not found with provided JID, trying to find by ID only:', messageId)
                    msg = await prisma.message.findFirst({
                        where: {
                            sessionId,
                            id: messageId
                        }
                    })

                    if (msg) {
                        logger.debug('Found message via ID fallback, resolved remoteJid:', msg.remoteJid)
                    }
                }

                if (!msg) return []

                return [{
                    key: {
                        remoteJid: msg.remoteJid,
                        id: msg.id,
                        fromMe: msg.fromMe,
                        participant: msg.participant
                    },
                    message: JSON.parse(msg.message, BufferJSON.reviver),
                    messageTimestamp: Number(msg.messageTimestamp),
                    pushName: msg.pushName,
                    status: msg.status
                }]
            }

            logger.info('Loading messages for:', jid, 'count:', count, 'cursor:', cursor)

            const queryOptions = {
                where: {
                    sessionId,
                    remoteJid: jid,
                },
                take: parseInt(count) || 25,
                orderBy: {
                    messageTimestamp: 'desc'
                }
            }

            // Handle cursor if provided
            if (cursor && cursor.before) {
                // Find the message to use as cursor
                const cursorMessage = await prisma.message.findFirst({
                    where: {
                        sessionId,
                        remoteJid: jid,
                        id: cursor.before.id,
                        fromMe: cursor.before.fromMe
                    }
                })

                if (cursorMessage) {
                    queryOptions.cursor = { pkId: cursorMessage.pkId }
                    queryOptions.skip = 1
                }
            }

            const messages = await prisma.message.findMany(queryOptions)

            return messages.map(msg => ({
                key: {
                    remoteJid: msg.remoteJid,
                    id: msg.id,
                    fromMe: msg.fromMe,
                    participant: msg.participant
                },
                message: JSON.parse(msg.message, BufferJSON.reviver),
                messageTimestamp: Number(msg.messageTimestamp),
                pushName: msg.pushName,
                status: msg.status
            })).reverse()
        } catch (error) {
            logger.info('DB Store - loadMessages', error)
            return []
        }
    }

    return {
        bind,
        loadMessages,
        chats: {
            get: (jid) => {
                // This is synchronous, but we need async to fetch from DB.
                // Baileys expects a synchronous Map-like interface for chats.
                // We might need to preload chats or change how we access them.
                // For now, let's try to return a promise-like object or null, 
                // but the best way is to load all chats into memory on init or use a cache.
                // Since we can't easily change the interface to async, we will load all chats into memory.
                return null
            },
            getAll: async (limit = 20, cursor = null, filter = null) => {
                const whereClause = { sessionId }

                // Add filter for chat type if provided
                if (filter) {
                    whereClause.id = { endsWith: filter }
                }

                const queryOptions = {
                    where: whereClause,
                    take: limit + 1, // Fetch one extra to check if there are more
                    orderBy: { lastUpdated: 'desc' }
                }

                // If cursor is provided, use it for pagination
                if (cursor) {
                    queryOptions.cursor = { pkId: parseInt(cursor) }
                    queryOptions.skip = 1 // Skip the cursor itself
                }

                const chats = await prisma.chat.findMany(queryOptions)

                // Check if there are more results
                const hasMore = chats.length > limit
                const paginatedChats = hasMore ? chats.slice(0, limit) : chats

                // Get the cursor for the next page
                const nextCursor = hasMore ? paginatedChats[paginatedChats.length - 1].pkId.toString() : null

                return {
                    chats: paginatedChats,
                    nextCursor,
                    hasMore
                }
            },
            // We need to implement a way to access chats synchronously if possible, 
            // or refactor the controller to use an async method.
            // Let's check how `chats` is used in `whatsapp.js` and `chatsController.js`.
            // It is used as `getSession(sessionId).store.chats`.
            // In `whatsapp.js`: `const chats = getSession(sessionId).store.chats; return [...chats.values()]...`
            // So it expects a Map.

            // We should load chats into this Map when the store is initialized or when requested.
            // However, loading ALL chats might be heavy.

            // Let's implement a `toJSON` or `values` method that fetches from DB?
            // No, `values()` is a generator on Map.

            // The best approach for now is to populate this Map with data from DB on startup,
            // or provide a custom `getChatList` function in `whatsapp.js` that queries the DB directly.
        },
        contacts: new Map(),
        messages: new Map(),
    }
}
