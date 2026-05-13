import type { TaskStatus } from './enums';

export interface Task {
  id: number;
  project_id: number;
  name: string;
  description?: string | null;
  assignee?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  progress: number;
  status: TaskStatus;
  order: number;
  dependency_ids?: number[];
  created_at: string;
}

export interface Milestone {
  id: number;
  project_id: number;
  name: string;
  date: string;
  reached: boolean;
  created_at: string;
}

export interface ProjectStats {
  total_tasks: number;
  completed_tasks: number;
  overall_progress: number;
}

export interface Project {
  id: number;
  company_id: number;
  project_status_id: number;
  name: string;
  description?: string | null;
  color: string;
  start_date?: string | null;
  end_date?: string | null;
  created_by: number;
  created_at: string;
  tasks?: Task[];
  milestones?: Milestone[];
  stats?: ProjectStats;
}
