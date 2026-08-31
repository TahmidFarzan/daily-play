
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL, useApiCache } from '@/composables/useApiCache'

const { clearByPrefix } = useApiCache()

export const groups = {
    User: 'User',
}

export const access = {
    ViewAny: 'View any',
    View: 'View',
    Create: 'Create',
    Update: 'Update',
    Delete: 'Delete',
    Restore: 'Restore',
    ForceDelete: 'Force delete',
}

export const getPermissions = async (authUser) => {
    if (!authUser) {
        return []
    }

    let permissions = authUser.user_permissions

    if (!Array.isArray(permissions)) {
        const apiUrl = route('search.user', { slugOrId: authUser.id, })
        const user = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_USER}:${authUser.id}`,
                ttl: apiCacheTTL.API_USER,
            }
        )

        permissions = user?.user_permissions || []
    }

    return permissions
}

export const clearPermissionCache = (userId) => {
    if (userId) {
        clearByPrefix(`${apiCacheKey.API_USER}:${userId}`)
        return
    }

    clearByPrefix(`${apiCacheKey.API_USER}:`)
}

export const hasPermission = async (authUser, module, permissionAccess) => {
    if (!authUser) {
        return false
    }

    if ( authUser.is_super_admin ) {
        return true
    }

    const permissions = await getPermissions( authUser )

    return permissions.some(
        permission =>
            permission.module ===
            module &&
            permission.access ===
            permissionAccess
    )
}

export const canAccessUser = async (authUser) => hasPermission(authUser, groups.User, access.ViewAny)
export const canCreateUser = async (authUser) => hasPermission(authUser, groups.User, access.Create)
export const canUpdateUser = async (authUser, user) => hasPermission(authUser, groups.User, access.Update)
export const canActiveInactiveUser = async (authUser, user) => {
    if (user?.is_default) {
        return false
    }
    if (user?.deleted_at) {
        return await hasPermission(
            authUser,
            groups.User,
            access.Restore
        )
    }
    else {
        return await hasPermission(
            authUser,
            groups.User,
            access.Delete
        )
    }
}
export const canDeleteUser = async (authUser, user) => {
    if (user?.is_default) {
        return false
    }
    return await hasPermission(
        authUser,
        groups.User,
        access.ForceDelete
    )
}

export const canAccessActivityLog = async (authUser) => authUser?.is_super_admin
export const canDeleteActivityLog = async (authUser) => authUser?.is_super_admin

export const canAccessQueueMonitor = (authUser) => authUser?.is_super_admin
export const canAccessLogViewer = (authUser) => authUser?.is_super_admin

export const canAccessSetting = (authUser) => authUser?.is_super_admin
