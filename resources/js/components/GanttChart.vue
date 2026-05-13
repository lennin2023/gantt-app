<script setup lang="ts">
import { computed, ref } from 'vue';

import type { GanttRow } from '@/types/gantt';

interface Props {
  rows: GanttRow[];
  startDate: Date;
  endDate: Date;
}

const props = defineProps<Props>();

const cellWidth = 40;
const rowHeight = 50;
const leftPanelWidth = 300;

const days = computed(() => {
  const result: {
    date: Date;
    isWeekend: boolean;
    isCurrentMonth: boolean;
  }[] = [];
  const current = new Date(props.startDate);

  while (current <= props.endDate) {
    const day = current.getDay();
    result.push({
      date: new Date(current),
      isWeekend: day === 0 || day === 6,
      isCurrentMonth: current.getMonth() === props.startDate.getMonth(),
    });
    current.setDate(current.getDate() + 1);
  }

  return result;
});

const months = computed(() => {
  const result: { label: string; days: number; startIndex: number }[] = [];
  let lastMonth = -1;

  days.value.forEach((day, index) => {
    const month = day.date.getMonth();

    if (month !== lastMonth) {
      result.push({
        label: day.date.toLocaleDateString('en-US', {
          month: 'short',
          year: 'numeric',
        }),
        days: 1,
        startIndex: index,
      });
      lastMonth = month;
    } else {
      result[result.length - 1].days++;
    }
  });

  return result;
});

const timelineWidth = computed(() => days.value.length * cellWidth);

const getTaskStyle = (row: GanttRow) => {
  if (!row.startDate || !row.endDate) {
    return null;
  }

  const startOffset = Math.ceil(
    (row.startDate.getTime() - props.startDate.getTime()) /
      (1000 * 60 * 60 * 24),
  );
  const duration = Math.ceil(
    row.endDate.getTime() - row.startDate.getTime() / (1000 * 60 * 60 * 24) + 1,
  );

  return {
    left: `${startOffset * cellWidth}px`,
    width: `${Math.max(duration * cellWidth, cellWidth)}px`,
    backgroundColor: row.color,
  };
};

const getMilestoneStyle = (row: GanttRow) => {
  if (!row.startDate) {
    return null;
  }

  const startOffset = Math.ceil(
    (row.startDate.getTime() - props.startDate.getTime()) /
      (1000 * 60 * 60 * 24),
  );

  return {
    left: `${startOffset * cellWidth + cellWidth / 2}px`,
  };
};

const scrollContainer = ref<HTMLElement | null>(null);

const scrollToToday = () => {
  if (!scrollContainer.value) {
    return;
  }

  const today = new Date();
  const daysSinceStart = Math.ceil(
    (today.getTime() - props.startDate.getTime()) / (1000 * 60 * 60 * 24),
  );
  const scrollPosition = Math.max(
    0,
    daysSinceStart * cellWidth - scrollContainer.value.clientWidth / 2,
  );
  scrollContainer.value.scrollLeft = scrollPosition;
};

defineExpose({ scrollToToday });
</script>

<template>
  <div
    class="flex h-full flex-col overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
  >
    <div
      class="flex border-b border-sidebar-border/70 dark:border-sidebar-border"
    >
      <div
        class="shrink-0 border-r border-sidebar-border/70 bg-muted/50 px-4 py-3 font-medium dark:border-sidebar-border"
        :style="{ width: `${leftPanelWidth}px` }"
      >
        <span class="text-sm">Task</span>
      </div>

      <div ref="scrollContainer" class="flex-1 overflow-x-auto">
        <div class="sticky top-0 z-10">
          <div class="flex bg-muted/50">
            <td
              v-for="(month, index) in months"
              :key="index"
              :style="{ width: `${month.days * cellWidth}px` }"
              class="border-r border-b border-sidebar-border/70 px-2 py-1.5 text-center text-sm font-medium dark:border-sidebar-border"
            >
              {{ month.label }}
            </td>
          </div>

          <div class="flex">
            <td
              v-for="(day, index) in days"
              :key="index"
              :style="{ width: `${cellWidth}px` }"
              class="border-r border-b border-sidebar-border/70 text-center text-xs dark:border-sidebar-border"
              :class="{
                'bg-muted/30': day.isWeekend,
                'text-muted-foreground': !day.isCurrentMonth,
              }"
            >
              <div class="py-1">{{ day.date.getDate() }}</div>
              <div class="text-[10px] uppercase">
                {{
                  day.date
                    .toLocaleDateString('en-US', {
                      weekday: 'short',
                    })
                    .slice(0, 2)
                }}
              </div>
            </td>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
      <div
        class="shrink-0 overflow-y-auto border-r border-sidebar-border/70 bg-card dark:border-sidebar-border"
        :style="{ width: `${leftPanelWidth}px` }"
      >
        <div
          v-for="row in rows"
          :key="row.id"
          class="flex items-center border-b border-sidebar-border/70 px-4 dark:border-sidebar-border"
          :style="{ height: `${rowHeight}px` }"
        >
          <div class="flex flex-col truncate">
            <span class="truncate text-sm font-medium">{{ row.name }}</span>
            <span
              v-if="row.assignee"
              class="truncate text-xs text-muted-foreground"
            >
              {{ row.assignee }}
            </span>
          </div>
        </div>
      </div>

      <div ref="scrollContainer" class="flex-1 overflow-auto">
        <div :style="{ width: `${timelineWidth}px` }">
          <div
            v-for="row in rows"
            :key="row.id"
            class="relative flex items-center border-b border-sidebar-border/70 dark:border-sidebar-border"
            :style="{ height: `${rowHeight}px` }"
          >
            <template
              v-if="row.type === 'task' && row.startDate && row.endDate"
            >
              <div
                class="absolute top-1/2 flex h-8 -translate-y-1/2 items-center rounded-md px-2 text-xs font-medium text-white shadow-sm"
                :style="getTaskStyle(row)"
              >
                <span class="truncate">{{ row.name }}</span>
                <span v-if="row.progress > 0" class="ml-auto pl-2">
                  {{ row.progress }}%
                </span>
              </div>

              <svg
                v-for="(depId, depIndex) in row.dependencies"
                :key="depIndex"
                class="pointer-events-none absolute"
                :style="{
                  left: '0',
                  top: '50%',
                  width: '100%',
                  height: '100%',
                }"
              >
                <path
                  v-if="depId"
                  :d="`M 0 25 Q 20 25 40 25`"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                  stroke-dasharray="4,2"
                  class="text-muted-foreground/50"
                />
              </svg>
            </template>

            <template v-else-if="row.type === 'milestone' && row.startDate">
              <div
                class="absolute top-1/2 h-6 w-6 -translate-y-1/2 rotate-45 border-2 bg-card"
                :style="getMilestoneStyle(row)"
                :class="
                  row.isReached
                    ? 'border-green-500 bg-green-500/20'
                    : 'border-yellow-500 bg-yellow-500/20'
                "
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
