#!/usr/bin/env python3
import cv2
import sys
import json
import numpy as np
import os

def detect_face(image_path, cascade_path):
    """Détecte un visage dans une image"""
    face_cascade = cv2.CascadeClassifier(cascade_path)
    
    img = cv2.imread(image_path)
    if img is None:
        return {'face_detected': False, 'error': 'Cannot read image'}
    
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # Paramètres comme dans le code Java
    faces = face_cascade.detectMultiScale(gray, 1.1, 5, 0, (100, 100))
    
    if len(faces) == 0:
        return {'face_detected': False, 'error': 'No face found'}
    
    # Prendre le plus grand visage
    largest_face = max(faces, key=lambda rect: rect[2] * rect[3])
    x, y, w, h = largest_face
    
    # Extraire le visage
    face = gray[y:y+h, x:x+w]
    
    # Redimensionner à 200x200 comme dans Java
    face_resized = cv2.resize(face, (200, 200))
    
    # Améliorer le contraste (CLAHE)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    face_enhanced = clahe.apply(face_resized)
    
    # Appliquer un flou léger
    face_blurred = cv2.GaussianBlur(face_enhanced, (3, 3), 0)
    
    # Sauvegarder l'image traitée
    output_path = image_path.replace('.jpg', '_face.jpg')
    cv2.imwrite(output_path, face_blurred)
    
    # Calculer la qualité (basée sur la variance de Laplacian)
    laplacian = cv2.Laplacian(face_enhanced, cv2.CV_64F)
    quality = laplacian.var()
    
    return {
        'face_detected': True,
        'quality': min(100, quality / 10),
        'face_position': {'x': int(x), 'y': int(y), 'width': int(w), 'height': int(h)},
        'processed_path': output_path
    }

def compare_faces(image1_path, image2_path):
    """Compare deux images de visage"""
    img1 = cv2.imread(image1_path, cv2.IMREAD_GRAYSCALE)
    img2 = cv2.imread(image2_path, cv2.IMREAD_GRAYSCALE)
    
    if img1 is None or img2 is None:
        return {'similarity': 0, 'error': 'Cannot read images'}
    
    # Redimensionner à la même taille
    img1 = cv2.resize(img1, (200, 200))
    img2 = cv2.resize(img2, (200, 200))
    
    # Égalisation des histogrammes
    img1 = cv2.equalizeHist(img1)
    img2 = cv2.equalizeHist(img2)
    
    # Template Matching
    result = cv2.matchTemplate(img1, img2, cv2.TM_CCOEFF_NORMED)
    template_similarity = np.max(result) * 100
    
    # Comparaison d'histogrammes
    hist1 = cv2.calcHist([img1], [0], None, [256], [0, 256])
    hist2 = cv2.calcHist([img2], [0], None, [256], [0, 256])
    hist_similarity = cv2.compareHist(hist1, hist2, cv2.HISTCMP_CORREL) * 100
    
    # Différence de pixels
    diff = cv2.absdiff(img1, img2)
    mse = np.mean(diff ** 2)
    pixel_similarity = max(0, 100 - (mse * 100 / 255))
    
    # Combinaison pondérée (comme dans Java)
    similarity = (template_similarity * 0.6) + (hist_similarity * 0.3) + (pixel_similarity * 0.1)
    
    return {'similarity': similarity}

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