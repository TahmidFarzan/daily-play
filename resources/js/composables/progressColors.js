const COLOR_LEVELS = [
    '#3B82F6',
    '#06B6D4',
    '#6366F1',
    '#8B5CF6',
    '#A855F7',
    '#14B8A6',
    '#22C55E',
    '#2563EB',
    '#0891B2',
    '#4F46E5',
    '#7C3AED',
    '#9333EA',
]

const SOFT_LEVELS = [
    '#EFF6FF',
    '#ECFEFF',
    '#EEF2FF',
    '#F5F3FF',
    '#FAF5FF',
    '#F0FDFA',
    '#F0FDF4',
    '#DBEAFE',
    '#CFFAFE',
    '#EEF2FF',
    '#F5F3FF',
    '#FAF5FF',
]

export const progressionLevelFor = (count) =>
    count <= 4 ? 0 : Math.floor((count - 5) / 2) + 1

export const progressionColor = (count) => {
    const level = progressionLevelFor(count)

    return COLOR_LEVELS[Math.min(level, COLOR_LEVELS.length - 1)]
}

export const softTintColor = (count) => {
    const level = progressionLevelFor(count)

    return SOFT_LEVELS[Math.min(level, SOFT_LEVELS.length - 1)]
}

export const segmentGroup = (position) =>
    position <= 4 ? 0 : Math.floor((position - 5) / 5) + 1

export const segmentColor = (position) => {
    const level = segmentGroup(position)

    return COLOR_LEVELS[Math.min(level, COLOR_LEVELS.length - 1)]
}

export const segmentColorAt = (index) => segmentColor(index + 1)