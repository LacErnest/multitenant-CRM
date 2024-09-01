export interface Comment {
  id?: string;
  content: string;
  creator?: {
    name: string;
    id: string;
  };
  status?: number;
  created_at?: string;
  updated_at?: string;
}
