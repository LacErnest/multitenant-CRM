import { TemplateType } from 'src/app/shared/types/template-type';

export interface TemplateUploaded {
  file: any; // TODO: add type
  type: TemplateType;
}
