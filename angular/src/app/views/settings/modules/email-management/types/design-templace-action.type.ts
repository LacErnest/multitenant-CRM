import { DesignTemplate } from '../interfaces/design-template';

type DesignTemplateAction = (
  designTemplate?: DesignTemplate,
  index?: number
) => void | Promise<void>;

export default DesignTemplateAction;
