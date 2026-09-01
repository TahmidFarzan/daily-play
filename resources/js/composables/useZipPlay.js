import { computed, ref } from 'vue'

export function useZipPlay(boardSource) {
    const rows = computed(() => boardSource.value?.rows || 0)
    const cols = computed(() => boardSource.value?.cols || 0)

    const cellKey = (row, col) => `${row}:${col}`

    const clueMap = computed(() => {
        const map = {}

        for (const clue of boardSource.value?.clues || []) {
            map[cellKey(clue.row, clue.col)] = clue.number
        }

        return map
    })

    const walls = computed(() => boardSource.value?.walls || [])

    const wallSet = computed(() => new Set(walls.value.map(
        (wall) => `${wall.row}:${wall.col}:${wall.direction}`,
    )))

    const totalCells = computed(() => rows.value * cols.value)

    const startCell = computed(() => {
        const startClue = (boardSource.value?.clues || []).find((clue) => clue.number === 1)

        return startClue ? { row: startClue.row, col: startClue.col } : null
    })

    const goalKey = computed(() => {
        let highest = null

        for (const clue of boardSource.value?.clues || []) {
            if (!highest || clue.number > highest.number) highest = clue
        }

        return highest ? cellKey(highest.row, highest.col) : null
    })

    const path = ref([])
    const currentCell = ref(null)
    const complete = ref(false)
    const backtrackCount = ref(0)

    let backtrackingInGesture = false

    const pathIndex = (row, col) => path.value.findIndex((cell) => cell.row === row && cell.col === col)

    const nextClue = computed(() => {
        let highest = 0

        for (const cell of path.value) {
            const clue = clueMap.value[cellKey(cell.row, cell.col)]

            if (clue) highest = Math.max(highest, clue)
        }

        return highest + 1
    })

    const blocksMove = (fromRow, fromCol, toRow, toCol) => {
        const deltaRow = toRow - fromRow
        const deltaCol = toCol - fromCol

        let wallKey = null

        if (deltaRow === 0 && deltaCol === 1) {
            wallKey = `${fromRow}:${fromCol}:right`
        } else if (deltaRow === 0 && deltaCol === -1) {
            wallKey = `${toRow}:${toCol}:right`
        } else if (deltaRow === 1 && deltaCol === 0) {
            wallKey = `${fromRow}:${fromCol}:down`
        } else if (deltaRow === -1 && deltaCol === 0) {
            wallKey = `${toRow}:${toCol}:down`
        }

        return wallKey !== null && wallSet.value.has(wallKey)
    }

    const isAdjacent = (fromRow, fromCol, toRow, toCol) =>
        Math.abs(toRow - fromRow) + Math.abs(toCol - fromCol) === 1

    const canStartDragAt = (cell) => {
        if (complete.value || !cell) return false

        if (path.value.length === 0) {
            return startCell.value !== null
                && cell.row === startCell.value.row
                && cell.col === startCell.value.col
        }

        const head = currentCell.value

        return head !== null && head.row === cell.row && head.col === cell.col
    }

    const startDrag = (cell) => {
        if (!canStartDragAt(cell)) return false

        if (path.value.length === 0) {
            path.value.push({ row: cell.row, col: cell.col })
            currentCell.value = { row: cell.row, col: cell.col }
        }

        backtrackingInGesture = false

        return true
    }

    const moveTo = (row, col) => {
        if (complete.value || !currentCell.value) return false

        if (currentCell.value.row === row && currentCell.value.col === col) return false

        const index = pathIndex(row, col)

        if (index !== -1) {
            path.value.splice(index + 1)
            currentCell.value = { row, col }

            if (!backtrackingInGesture) {
                backtrackCount.value += 1
                backtrackingInGesture = true
            }

            return true
        }

        if (!isAdjacent(currentCell.value.row, currentCell.value.col, row, col)) return false

        if (blocksMove(currentCell.value.row, currentCell.value.col, row, col)) return false

        const clue = clueMap.value[cellKey(row, col)]

        if (clue && clue !== nextClue.value) return false

        path.value.push({ row, col })
        currentCell.value = { row, col }
        backtrackingInGesture = false

        if (path.value.length === totalCells.value) {
            complete.value = true
        }

        return true
    }

    const stepToward = (target) => {
        const head = currentCell.value

        if (!head || (head.row === target.row && head.col === target.col)) return null

        const deltaRow = target.row - head.row
        const deltaCol = target.col - head.col

        const horizontal = deltaCol !== 0 ? { row: head.row, col: head.col + Math.sign(deltaCol) } : null
        const vertical = deltaRow !== 0 ? { row: head.row + Math.sign(deltaRow), col: head.col } : null
        const candidates = [horizontal, vertical].filter(Boolean)

        const preferred = candidates.find((cell) => pathIndex(cell.row, cell.col) !== -1)

        if (preferred) return preferred

        if (Math.abs(deltaRow) > Math.abs(deltaCol)) return vertical ?? horizontal

        return horizontal ?? vertical
    }

    const traceToward = (target) => {
        let guard = totalCells.value

        while (guard > 0) {
            if (complete.value) return

            const step = stepToward(target)

            if (!step) return

            if (!moveTo(step.row, step.col)) return

            guard -= 1
        }
    }

    return {
        rows,
        cols,
        cellKey,
        clueMap,
        walls,
        totalCells,
        startCell,
        goalKey,
        path,
        currentCell,
        complete,
        backtrackCount,
        canStartDragAt,
        startDrag,
        moveTo,
        traceToward,
    }
}