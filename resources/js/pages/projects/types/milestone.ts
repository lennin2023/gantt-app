export interface CreateMilestonePayload {
  name: string;
  date: string;
  reached?: boolean;
}

export type UpdateMilestonePayload = Partial<CreateMilestonePayload>;
