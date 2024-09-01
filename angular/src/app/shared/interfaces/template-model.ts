export interface TemplateModel {
  id: string;
  name: string;
  created_at: string;
  updated_at: string;
  is_deletion_allowed?: boolean;
  is_edit_allowed?: boolean;
}

export interface TemplateList {
  data: TemplateModel[];
  count: number;
}
