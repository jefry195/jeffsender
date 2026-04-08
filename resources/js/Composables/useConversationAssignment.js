import { ref } from 'vue'

import axios from 'axios'

export function useConversationAssignment() {
  const assignments = ref([])
  const workspaceUsers = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchAssignments = async (conversationId) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get(`/api/conversations/${conversationId}/assignments`)
      assignments.value = response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch assignments'
    } finally {
      loading.value = false
    }
  }

  const fetchWorkspaceUsers = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.get('/api/workspace-users')
      workspaceUsers.value = response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch users'
    } finally {
      loading.value = false
    }
  }

  const assignUsers = async (conversationId, userIds, notes = null) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.post('/api/conversation-assignments', {
        conversation_id: conversationId,
        assigned_to: userIds,
        notes: notes,
        conversation_link: window.location.href
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to assign users'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateAssignment = async (assignmentId, status, notes = null) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.put(`/api/conversation-assignments/${assignmentId}`, {
        status: status,
        notes: notes
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update assignment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteAssignment = async (assignmentId) => {
    loading.value = true
    error.value = null
    try {
      const response = await axios.delete(`/api/conversation-assignments/${assignmentId}`)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete assignment'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    assignments,
    workspaceUsers,
    loading,
    error,
    fetchAssignments,
    fetchWorkspaceUsers,
    assignUsers,
    updateAssignment,
    deleteAssignment
  }
}
