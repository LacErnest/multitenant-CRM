/**
 * This interface will be used to add dynamic options to the popup displayed on each line of the table.
 */
export interface MenuOption {
  title: string;
  icon?: string;
  visible?($event: any): boolean;
  onAction?($event: any): void;
}
