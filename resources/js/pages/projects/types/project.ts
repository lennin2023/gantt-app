export interface CreateProjectPayload {
  company_id: number;
  name: string;
  description?: string | null;
  color?: string | null;
  start_date?: string | null;
  end_date?: string | null;
}

export type UpdateProjectPayload = Partial<CreateProjectPayload>;
