import { ByteDisplayPipe } from './byte-display.pipe';

describe('ByteDisplayPipe', () => {
  it('create an instance', () => {
    const pipe = new ByteDisplayPipe();
    expect(pipe).toBeTruthy();
  });
});
