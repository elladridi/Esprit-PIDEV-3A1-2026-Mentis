#!/usr/bin/env python3
import cv2
import sys
import json
import numpy as np
import os


def detect_face(image_path, cascade_path):
    """Detect a face, crop it, normalize it, and save processed face image."""
    face_cascade = cv2.CascadeClassifier(cascade_path)

    if face_cascade.empty():
        return {'face_detected': False, 'error': 'Cannot load cascade file'}

    img = cv2.imread(image_path)
    if img is None:
        return {'face_detected': False, 'error': 'Cannot read image'}

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    faces = face_cascade.detectMultiScale(
        gray,
        scaleFactor=1.05,
        minNeighbors=6,
        flags=0,
        minSize=(100, 100)
    )

    if len(faces) == 0:
        return {'face_detected': False, 'error': 'No face found'}

    # Take the largest detected face
    x, y, w, h = max(faces, key=lambda rect: rect[2] * rect[3])

    # Add a little padding around the face
    pad = 10
    x1 = max(0, x - pad)
    y1 = max(0, y - pad)
    x2 = min(gray.shape[1], x + w + pad)
    y2 = min(gray.shape[0], y + h + pad)

    face = gray[y1:y2, x1:x2]
    if face.size == 0:
        return {'face_detected': False, 'error': 'Invalid face crop'}

    # Standardize size
    face_resized = cv2.resize(face, (200, 200))

    # Improve contrast
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    face_enhanced = clahe.apply(face_resized)

    # Save processed image
    base, _ = os.path.splitext(image_path)
    output_path = base + '_face.jpg'
    ok = cv2.imwrite(output_path, face_enhanced)
    if not ok:
        return {'face_detected': False, 'error': 'Failed to save processed image'}

    # Sharpness / blur quality estimate
    laplacian = cv2.Laplacian(face_enhanced, cv2.CV_64F)
    quality = float(laplacian.var())

    return {
        'face_detected': True,
        'quality': round(min(100.0, quality / 10.0), 2),
        'face_position': {
            'x': int(x),
            'y': int(y),
            'width': int(w),
            'height': int(h)
        },
        'processed_path': output_path
    }


def compare_faces(image1_path, image2_path):
    """Compare two processed face images using OpenCV LBPH."""
    img1 = cv2.imread(image1_path, cv2.IMREAD_GRAYSCALE)
    img2 = cv2.imread(image2_path, cv2.IMREAD_GRAYSCALE)

    if img1 is None or img2 is None:
        return {'similarity': 0, 'error': 'Cannot read images'}

    img1 = cv2.resize(img1, (200, 200))
    img2 = cv2.resize(img2, (200, 200))

    # Normalize contrast
    img1 = cv2.equalizeHist(img1)
    img2 = cv2.equalizeHist(img2)

    # Check that opencv-contrib is actually available
    if not hasattr(cv2, 'face'):
        return {'similarity': 0, 'error': 'cv2.face module not available'}

    try:
        recognizer = cv2.face.LBPHFaceRecognizer_create()
    except Exception as e:
        return {'similarity': 0, 'error': f'Failed to create LBPH recognizer: {str(e)}'}

    # Train on image1 as label 0, then predict image2
    recognizer.train([img1], np.array([0], dtype=np.int32))
    label, confidence = recognizer.predict(img2)

    # For LBPH: lower confidence = better match
    # Convert to a demo-friendly similarity percentage
    similarity = max(0.0, min(100.0, 100.0 - float(confidence)))

    return {
        'similarity': round(similarity, 2),
        'confidence': round(float(confidence), 2),
        'predicted_label': int(label)
    }


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No command specified'}))
        sys.exit(1)

    command = sys.argv[1]

    if command == 'detect':
        if len(sys.argv) < 4:
            print(json.dumps({'error': 'Missing arguments'}))
            sys.exit(1)

        image_path = sys.argv[2]
        cascade_path = sys.argv[3]
        result = detect_face(image_path, cascade_path)
        print(json.dumps(result))

    elif command == 'compare':
        if len(sys.argv) < 4:
            print(json.dumps({'error': 'Missing arguments'}))
            sys.exit(1)

        image1_path = sys.argv[2]
        image2_path = sys.argv[3]
        result = compare_faces(image1_path, image2_path)
        print(json.dumps(result))

    else:
        print(json.dumps({'error': 'Unknown command'}))