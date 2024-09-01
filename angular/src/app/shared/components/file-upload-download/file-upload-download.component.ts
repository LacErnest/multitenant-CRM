import { DOCUMENT } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
} from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { UploadedFileInfo } from 'src/app/shared/interfaces/uploaded-file-info';
import { ByteDisplayPipe } from 'src/app/shared/pipes/byte-display.pipe';
import {
  FileRestrictions,
  UploadService,
} from 'src/app/shared/services/upload.service';

@Component({
  selector: 'oz-finance-file-upload-download',
  templateUrl: './file-upload-download.component.html',
  styleUrls: ['./file-upload-download.component.scss'],
  providers: [ByteDisplayPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class FileUploadDownloadComponent implements OnInit {
  @Input() public controlName: string;
  @Input() public fileName: string;
  @Input() public fileType: string;
  @Input() public isSavedFile: boolean;
  @Input() public readonlyMode = false;
  @Input() public onlyDownload = false;

  @Output() public fileDownloadClicked: EventEmitter<string> =
    new EventEmitter<string>();
  @Output() public fileDeleteClicked: EventEmitter<string> =
    new EventEmitter<string>();
  @Output() public fileUploaded: EventEmitter<UploadedFileInfo> =
    new EventEmitter<UploadedFileInfo>();

  private fileRestrictions: FileRestrictions = {
    size: 50 * 1024 * 1024,
    allowedFileTypes: [
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/msword',
      'application/pdf',
    ],
  };

  constructor(
    @Inject(DOCUMENT) private document: Document,
    private bytePipe: ByteDisplayPipe,
    private toastrService: ToastrService,
    private uploadService: UploadService
  ) {}

  ngOnInit(): void {}

  public async uploadFile(files: File[]): Promise<void> {
    const [file] = files;
    const { file: uploaded } = await this.uploadService.readFile(
      file,
      this.fileRestrictions
    );

    if (uploaded) {
      this.fileUploaded.emit({
        controlName: this.controlName,
        fileName: file.name,
        uploaded,
      });

      this.toastrService.info(
        `File ${file.name} (${this.bytePipe.transform(file.size)}) parsed successfully. Save the form to finalize upload.`,
        'Upload'
      );
    } else {
      this.clearInput();
    }
  }

  public downloadDocument(): void {
    if (this.isSavedFile) {
      this.fileDownloadClicked.emit(this.controlName);
    }
  }

  public deleteDocument(): void {
    if (this.isSavedFile) {
      this.fileDeleteClicked.emit(this.fileName);
    }
  }

  public triggerInputChange(input: HTMLInputElement): void {
    input.click();
  }

  private clearInput(): void {
    (this.document.getElementById(this.controlName) as HTMLInputElement).value =
      '';
  }
}
