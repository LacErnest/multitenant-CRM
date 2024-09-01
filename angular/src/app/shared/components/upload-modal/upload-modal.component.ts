import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import { DOCUMENT } from '@angular/common';
import {
  Component,
  ElementRef,
  Inject,
  OnInit,
  Renderer2,
  ViewChild,
} from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';

import { Subject } from 'rxjs';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';
import { FileRestrictions, UploadService } from '../../services/upload.service';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'oz-finance-upload-modal',
  templateUrl: './upload-modal.component.html',
  styleUrls: ['./upload-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('300ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('200ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('modalAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(1rem)' }),
        animate(
          '300ms ease-in',
          style({ opacity: 1, transform: 'translateY(0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translateY(0)' }),
        animate(
          '200ms ease-out',
          style({ opacity: 0, transform: 'translateY(1rem)' })
        ),
      ]),
    ]),
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class UploadModalComponent implements OnInit {
  @ViewChild('upload_file', { static: false }) public upload_file: ElementRef;

  public showUploadModal = false;
  public uploadForm: FormGroup;

  private modalSubject: Subject<any>;

  public constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private renderer: Renderer2,
    private toastService: ToastrService,
    private uploadService: UploadService
  ) {}

  public ngOnInit(): void {}

  public submitUploadForm(): void {
    if (this.uploadForm.valid) {
      const val = { ...this.uploadForm.getRawValue() };
      this.closeModal(val);
    }
  }

  public openModal(): Subject<any> {
    this.initUploadForm();
    this.showUploadModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showUploadModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public async uploadFile(files: File[]): Promise<void> {
    const [file] = files;
    const fileRestrictions: FileRestrictions = {
      size: 10 * 1024 * 1024,
      allowedFileTypes: [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'application/pdf',
      ],
    };

    const { file: uploaded }: any = await this.uploadService.readFile(
      file,
      fileRestrictions
    );

    if (uploaded) {
      this.patchUploadedFile(uploaded);
      this.toastService.info(
        `File ${file.name} parsed successfully. Save this modal to finalize the upload.`,
        'Upload'
      );
    }
  }

  private closeModal(value?: any): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showUploadModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initUploadForm(): void {
    this.uploadForm = this.fb.group({
      file_name: new FormControl(undefined, [
        Validators.required,
        Validators.maxLength(128),
      ]),
      upload_file: new FormControl(undefined, Validators.required),
    });
  }

  private patchUploadedFile(uploaded): void {
    this.uploadForm.get('upload_file').patchValue(uploaded);
  }
}
