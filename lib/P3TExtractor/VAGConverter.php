<?php
/**
 * Converts a PlayStation VAG sound file to a WAV file
 * This is heavily based on the psxsdk sources found at:
 * https://code.google.com/p/psxsdk/source/browse/trunk/psxsdk/tools/vag2wav.c
 *
 * @package PS3Theme.net
 * @subpackage P3T Extractor
 * @access public
 * @author Hoshang Sadiq
 * @copyright 2011
 *
 * @changelog
 * @version 1.0
 * @date 06/05/2011
 */

namespace P3TExtractor;

class VAGConverter
{
    private $vag = '';
    private $wav = '';
    private $f, $w = null;

    public function __construct( $vag, $wav )
    {
        $this->vag = $vag;
        $this->wav = $wav;

        $this->f = fopen( $this->vag, 'rb' );
        if ( !$this->f ) {
            throw new Exception( 'File ' . $this->vag . ' is not writable' );
        }

        $this->w = fopen( $this->wav, 'wb' );
        if ( !$this->w ) {
            throw new Exception( 'File ' . $this->wav . ' is not writable' );
        }

        if ( strncmp( fread( $this->f, 4 ), 'VAGp', 4 ) ) {
            throw new Exception( 'File ' . $this->vag . ' is not a VAG file' );
        }

        $h = array( array( 0.0,       0.0 ),
                    array( 0.9375,    0.0 ),
                    array( 1.796875, -0.8125 ),
                    array( 1.53125,  -0.859375 ),
                    array( 1.90625,  -0.9375 ) );

        // get file version, data size, frequency and description
        fseek( $this->f, 4 );
        $version = unpack( 'N', fread( $this->f, 4 ) );
        $version = $version[1];
        fseek( $this->f, 12 );
        $datasize = unpack( 'N', fread( $this->f, 4 ) );
        $datasize = $datasize[1];
        fseek( $this->f, 16 );
        $frequency = unpack( 'N', fread( $this->f, 4 ) );
        $frequency = $frequency[1];
        fseek( $this->f, 32 );
        $description = unpack( 'a*', fread( $this->f, 16 ) );
        $description = $description[1];

        // set the filepointer to after the header information
        fseek( $this->f, 64, SEEK_SET );

        // Write header chunk
        $this->write( 'RIFF' );
        fseek( $this->w, 4, SEEK_CUR ); // Skip file size field for now
        $this->write( 'WAVEfmt ' ); // Write fmt chunk

        // Write chunk 1 size in little endian format
        $this->write( "\x10", 0, 0, 0 );

        // Write audio format (1 = PCM) and the number of channels (1)
        $this->write( 1, 0, 1, 0 );

        // Write sample rate
        $this->write( $frequency & 0xFF, ( $frequency >> 8 ) & 0xFF, 0, 0 );

        // Write byte rate (SampleRate * NumChannels * BitsPerSample/8)
        // That would be 44100*1*(16/8), thus 88200.
        $mult = $frequency * 2;
        $this->write( $mult & 0xFF, ( $mult >> 8 ) & 0xFF,
            ( $mult >> 16 ) & 0xFF, ( $mult >> 24 ) & 0xFF );

        // Write block align (NumChannels * BitsPerSample/8), thus 2
        $this->write( 2, 0 );

        // Write BitsPerSample
        $this->write( 16, 0 );

        fprintf( $this->w, 'data' ); // Write data chunk

        // Skip SubChunk2Size, we will return to it later
        fseek( $this->w, 4, SEEK_CUR );

        // Now write data...
        $s_1 = $s_2 = 0.0;
        while ( ftell( $this->f ) < ( $datasize + 48 ) ) {
            $predict_nr = ord( fgetc( $this->f ) );
            $shift_factor = $predict_nr & 0xF;
            $predict_nr >>= 4;
            $flags = ord( fgetc( $this->f ) ); // flags;
            if ( $flags == 7 ) {
                break;
            }
            for ( $i = 0; $i < 28; $i += 2 ) {
                $d = ord( fgetc( $this->f ) );
                $s = ( $d & 0xF ) << 12;
                if ( $s & 0x8000 ) {
                    $s |= 0xFFFF0000;
                }
                $samples[$i] = ( double )( $s >> $shift_factor );
                $s = ( $d & 0xF0 ) << 8;
                if ( $s & 0x8000 ) {
                    $s |= 0xFFFF0000;
                }
                $samples[$i + 1] = ( double )( $s >> $shift_factor );
            }

            for ( $i = 0; $i < 28; $i++ ) {
                $samples[$i] = $samples[$i] + $s_1 * $h[$predict_nr][0] + $s_2 * $h[$predict_nr][1];
                $s_2 = $s_1;
                $s_1 = $samples[$i];
                $d = ( int )( $samples[$i] + 0.5 );
                $this->write( $d & 0xFF, $d >> 8 );
            }
        }
        $filesize = ftell( $this->w ) - 8; // get the filesize minus 8

        // Now write ChunkSize
        fseek( $this->w, 4 );

        $this->write( $filesize & 0xFF, ( $filesize >> 8 ) & 0xFF,
            ( $filesize >> 16 ) & 0xFF, ( $filesize >> 24 ) & 0xFF );

        // Now write Subchunk2Size
        fseek( $this->w, 40 );

        $filesize -= 36; // remove 36 from the filesize (equivalent to -44)
        $this->write( $filesize & 0xFF, ( $filesize >> 8 ) & 0xFF,
            ( $filesize >> 16 ) & 0xFF, ( $filesize >> 24 ) & 0xFF );

        fclose( $this->w );
        fclose( $this->f );
    }

    private function write()
    {
        $args = func_get_args();
        $write = '';
        foreach ( $args as $arg ) {
            if ( is_int( $arg ) ) {
                $write .= chr( $arg );
            } else {
                $write .= $arg;
            }
        }
        fwrite( $this->w, $write );
    }
}
