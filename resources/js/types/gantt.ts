export interface GanttTask {
  id: number;
  project_id: number;
  name: string;
  description: string | null;
  assignee: string | null;
  start_date: string | null;
  end_date: string | null;
  progress: number;
  status: string;
  order: number;
  dependency_ids: number[];
  created_at: string;
}

export interface GanttMilestone {
  id: number;
  project_id: number;
  name: string;
  target_date: string | null;
  reached: boolean;
  created_at: string;
}

export interface GanttProject {
  id: number;
  name: string;
  description: string | null;
  color: string;
  start_date: string | null;
  end_date: string | null;
  created_at: string;
  tasks: GanttTask[];
  milestones: GanttMilestone[];
}

export interface GanttTimelineCell {
  date: Date;
  isWeekend: boolean;
  isCurrentMonth: boolean;
}

export interface GanttRow {
  id: number;
  type: 'task' | 'milestone';
  name: string;
  startDate: Date | null;
  endDate: Date | null;
  progress: number;
  status: string;
  assignee: string | null;
  color: string;
  dependencies: number[];
  isReached?: boolean;
}
