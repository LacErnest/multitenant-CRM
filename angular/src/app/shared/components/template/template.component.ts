import {
  ChangeDetectionStrategy,
  Component,
  EventEmitter,
  Input,
  OnInit,
  Output,
} from '@angular/core';
import { Template } from 'src/app/shared/interfaces/template';
import { TemplatesService } from 'src/app/shared/services/templates.service';
import {
  FileRestrictions,
  UploadService,
} from 'src/app/shared/services/upload.service';
import { TemplateType } from 'src/app/shared/types/template-type';
import { GlobalService } from '../../../core/services/global.service';
import { UserRole } from '../../enums/user-role.enum';

@Component({
  selector: 'oz-finance-template',
  templateUrl: './template.component.html',
  styleUrls: ['./template.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class TemplateComponent implements OnInit {
  @Input() public template: Template;
  @Input() public templateLabel: string;
  @Input() public templateType: TemplateType;
  @Input() public isLastItem: boolean;
  public isLoading: boolean;

  @Output() public downloadClicked: EventEmitter<TemplateType> =
    new EventEmitter<TemplateType>();
  @Output() public showTemplateVariablesClicked: EventEmitter<TemplateType> =
    new EventEmitter<TemplateType>();
  @Output() public viewClicked: EventEmitter<TemplateType> =
    new EventEmitter<TemplateType>();

  private fileRestrictions: FileRestrictions = {
    size: 50 * 1024 * 1024,
    allowedFileTypes: [
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/msword',
    ],
  };

  public constructor(
    private templatesService: TemplatesService,
    private uploadService: UploadService,
    private globalService: GlobalService
  ) {}

  public ngOnInit(): void {}

  public onDownloadClicked(): void {
    this.downloadClicked.emit(this.templateType);
  }

  public showTemplateVariables(): void {
    this.showTemplateVariablesClicked.emit(this.templateType);
  }

  public onViewClicked(): void {
    this.viewClicked.emit(this.templateType);
  }

  public triggerInputChange(input: HTMLInputElement): void {
    input.click();
  }

  public async uploadFile(files: any, type: TemplateType): Promise<void> {
    const [file] = files;

    const { file: uploaded } = await this.uploadService.readFile(
      file,
      this.fileRestrictions
    );

    if (uploaded) {
      this.templatesService.emitTemplateUpload({ file: uploaded, type });
    } else {
      this.templatesService.clearInput(type);
    }
  }

  public isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }
}
